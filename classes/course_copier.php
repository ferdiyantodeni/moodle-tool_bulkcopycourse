<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_bulkcopycourse;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Core course copy execution engine.
 */
class course_copier {

    /**
     * Copy a single course from template with no user data.
     *
     * @param \stdClass $item Course item object containing target and source properties.
     * @param int|null $userid User ID initiating the copy (default current user or admin).
     * @return int Created course ID.
     * @throws \moodle_exception
     */
    public static function copy_course(\stdClass $item, ?int $userid = null): int {
        global $DB, $USER, $CFG;

        // Increase limits for course backup and restore operations.
        \core_php_time_limit::raise(600);
        raise_memory_limit(MEMORY_EXTRA);

        if (empty($userid)) {
            $admin = get_admin();
            $userid = !empty($USER->id) ? $USER->id : ($admin ? $admin->id : 2);
        }

        // 1. Verify source course.
        $sourcecourse = $DB->get_record('course', ['shortname' => $item->copyshortname]);
        if (!$sourcecourse) {
            throw new \moodle_exception(
                'err_sourcecoursenotfound',
                'tool_bulkcopycourse',
                '',
                $item->copyshortname
            );
        }

        // 2. Verify destination category.
        $categoryid = (int)$item->category;
        if (!$DB->record_exists('course_categories', ['id' => $categoryid])) {
            throw new \moodle_exception(
                'err_categorynotfound',
                'tool_bulkcopycourse',
                '',
                $item->category
            );
        }

        // 3. Verify target shortname uniqueness.
        if ($DB->record_exists('course', ['shortname' => $item->shortname])) {
            throw new \moodle_exception(
                'err_shortnametaken',
                'tool_bulkcopycourse',
                '',
                $item->shortname
            );
        }

        // 4. Create empty shell for target course.
        $newcourseid = \restore_dbops::create_new_course(
            $item->fullname,
            $item->shortname,
            $categoryid
        );

        if (!$newcourseid) {
            throw new \moodle_exception('Could not create destination course record.');
        }

        $backupfile = null;

        try {
            // 5. Perform Backup of source course (MODE_SAMESITE for intra-site duplication, NO user data).
            $bc = new \backup_controller(
                \backup::TYPE_1COURSE,
                $sourcecourse->id,
                \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO,
                \backup::MODE_SAMESITE,
                $userid
            );

            $bplan = $bc->get_plan();

            // Exclude all user data & role assignments (clean copy).
            self::set_setting_if_exists($bplan, 'users', 0);
            self::set_setting_if_exists($bplan, 'anonymize', 0);
            self::set_setting_if_exists($bplan, 'role_assignments', 0);
            self::set_setting_if_exists($bplan, 'user_files', 0);
            self::set_setting_if_exists($bplan, 'comments', 0);
            self::set_setting_if_exists($bplan, 'badges', 0);
            self::set_setting_if_exists($bplan, 'calendarevents', 0);

            // Include content, activities, sections, blocks, filters.
            self::set_setting_if_exists($bplan, 'activities', 1);
            self::set_setting_if_exists($bplan, 'blocks', 1);
            self::set_setting_if_exists($bplan, 'filters', 1);
            self::set_setting_if_exists($bplan, 'competencies', 1);
            self::set_setting_if_exists($bplan, 'customfield', 1);
            self::set_setting_if_exists($bplan, 'contentbankcontent', 1);

            $bc->execute_plan();
            $backupid = $bc->get_backupid();
            $results = $bc->get_results();
            $backupfile = $results['backup_destination'] ?? null;
            $backupbasepath = $bplan->get_basepath();
            $bc->destroy();

            // Extract the backup archive into the temp directory so restore_controller can read the backup content.
            if ($backupfile && is_object($backupfile) && method_exists($backupfile, 'extract_to_pathname')) {
                if (!file_exists($backupbasepath)) {
                    check_dir_exists($backupbasepath, true, true);
                }
                $fp = get_file_packer('application/vnd.moodle.backup');
                $backupfile->extract_to_pathname($fp, $backupbasepath);
            }

            // 6. Perform Restore into the new target course.
            $rc = new \restore_controller(
                $backupid,
                $newcourseid,
                \backup::INTERACTIVE_NO,
                \backup::MODE_SAMESITE,
                $userid,
                \backup::TARGET_NEW_COURSE
            );

            // Execute precheck only if controller is in STATUS_NEED_PRECHECK state.
            if ($rc->get_status() === \backup::STATUS_NEED_PRECHECK) {
                $rc->execute_precheck();
            }

            $rplan = $rc->get_plan();
            if ($rplan) {
                // Exclude user data on restore.
                self::set_setting_if_exists($rplan, 'users', 0);
                self::set_setting_if_exists($rplan, 'role_assignments', 0);
                self::set_setting_if_exists($rplan, 'user_files', 0);
                self::set_setting_if_exists($rplan, 'comments', 0);
                self::set_setting_if_exists($rplan, 'badges', 0);
                self::set_setting_if_exists($rplan, 'calendarevents', 0);

                // Include activities, blocks, filters.
                self::set_setting_if_exists($rplan, 'activities', 1);
                self::set_setting_if_exists($rplan, 'blocks', 1);
                self::set_setting_if_exists($rplan, 'filters', 1);
            }

            $rc->execute_plan();
            $rc->destroy();

        } catch (\Throwable $e) {
            // Cleanup partial course if restore fails.
            delete_course($newcourseid, false);
            if ($backupfile && is_object($backupfile) && method_exists($backupfile, 'delete')) {
                $backupfile->delete();
            }
            if (!empty($backupbasepath) && file_exists($backupbasepath)) {
                fulldelete($backupbasepath);
            }
            throw $e;
        }

        // Delete temporary backup archive file / directory if created.
        if ($backupfile && is_object($backupfile) && method_exists($backupfile, 'delete')) {
            $backupfile->delete();
        }
        if (!empty($backupbasepath) && file_exists($backupbasepath)) {
            fulldelete($backupbasepath);
        }

        // 7. Update course settings with user provided values.
        $updatecourse = new \stdClass();
        $updatecourse->id = $newcourseid;
        $updatecourse->fullname = $item->fullname;
        $updatecourse->shortname = $item->shortname;
        $updatecourse->category = $categoryid;
        $updatecourse->idnumber = !empty($item->idnumber) ? trim($item->idnumber) : '';
        $updatecourse->visible = isset($item->visible) ? (int)$item->visible : 1;

        if (!empty($item->startdate)) {
            $updatecourse->startdate = (int)$item->startdate;
        }
        if (!empty($item->enddate)) {
            $updatecourse->enddate = (int)$item->enddate;
        }

        $DB->update_record('course', $updatecourse);

        // 8. Configure enrolment method if specified.
        if (!empty($item->enrols)) {
            self::configure_enrolment($newcourseid, $item->enrols);
        }

        // 9. Rebuild course cache.
        rebuild_course_cache($newcourseid, true);

        return $newcourseid;
    }

    /**
     * Safely set a backup/restore plan setting if it exists.
     *
     * @param \base_plan $plan
     * @param string $name
     * @param mixed $value
     */
    protected static function set_setting_if_exists(?\base_plan $plan, string $name, $value): void {
        if (!$plan) {
            return;
        }
        if ($plan->setting_exists($name)) {
            $setting = $plan->get_setting($name);
            if ($setting->get_status() === \base_setting::NOT_LOCKED) {
                $setting->set_value($value);
            }
        }
    }

    /**
     * Ensure specified enrolment method is enabled on the course.
     *
     * @param int $courseid
     * @param string $enrolmethod
     */
    protected static function configure_enrolment(int $courseid, string $enrolmethod): void {
        global $DB;

        $methods = explode(',', strtolower($enrolmethod));
        foreach ($methods as $method) {
            $method = trim($method);
            if (empty($method)) {
                continue;
            }

            $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => $method]);
            if ($instance) {
                if ($instance->status != ENROL_INSTANCE_ENABLED) {
                    $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, ['id' => $instance->id]);
                }
            } else {
                $course = $DB->get_record('course', ['id' => $courseid]);
                $plugin = enrol_get_plugin($method);
                if ($plugin && $course) {
                    $plugin->add_instance($course);
                }
            }
        }
    }
}
