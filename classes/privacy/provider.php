<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy Subsystem implementation for tool_bulkcopycourse.
 *
 * @package     tool_bulkcopycourse
 * @category    privacy
 * @copyright   2026 Ferdiyanto Deni Sanjaya
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_bulkcopycourse\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for tool_bulkcopycourse.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe the metadata stored by this plugin.
     *
     * @param collection $collection The collection of metadata to add to.
     * @return collection The modified collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'tool_bulkcopycourse_jobs',
            [
                'userid' => 'privacy:metadata:jobs:userid',
                'name' => 'privacy:metadata:jobs:name',
                'status' => 'privacy:metadata:jobs:status',
                'totalcourses' => 'privacy:metadata:jobs:totalcourses',
                'timecreated' => 'privacy:metadata:jobs:timecreated',
                'timemodified' => 'privacy:metadata:jobs:timemodified',
            ],
            'privacy:metadata:jobs'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
                 WHERE c.contextlevel = :contextlevel
                   AND EXISTS (
                       SELECT 1
                         FROM {tool_bulkcopycourse_jobs} j
                        WHERE j.userid = :userid
                   )";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_SYSTEM,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $sql = "SELECT DISTINCT userid
                  FROM {tool_bulkcopycourse_jobs}";

        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $context = \context_system::instance();

        if (!$contextlist->has_context($context)) {
            return;
        }

        $jobs = $DB->get_records('tool_bulkcopycourse_jobs', ['userid' => $userid]);
        if (!empty($jobs)) {
            $data = [];
            foreach ($jobs as $job) {
                $data[] = [
                    'name' => $job->name,
                    'status' => $job->status,
                    'totalcourses' => $job->totalcourses,
                    'completedcourses' => $job->completedcourses,
                    'failedcourses' => $job->failedcourses,
                    'timecreated' => \core_privacy\local\request\transform::datetime($job->timecreated),
                    'timemodified' => \core_privacy\local\request\transform::datetime($job->timemodified),
                ];
            }

            \core_privacy\local\request\writer::with_context($context)->export_data(
                [get_string('pluginname', 'tool_bulkcopycourse'), get_string('history', 'tool_bulkcopycourse')],
                (object)['jobs' => $data]
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records('tool_bulkcopycourse_items');
        $DB->delete_records('tool_bulkcopycourse_jobs');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $context = \context_system::instance();

        if (!$contextlist->has_context($context)) {
            return;
        }

        $jobids = $DB->get_fieldset_select(
            'tool_bulkcopycourse_jobs',
            'id',
            'userid = :userid',
            ['userid' => $userid]
        );

        if (!empty($jobids)) {
            list($insql, $params) = $DB->get_in_or_equal($jobids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('tool_bulkcopycourse_items', "jobid $insql", $params);
            $DB->delete_records('tool_bulkcopycourse_jobs', ['userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $jobids = $DB->get_fieldset_select(
            'tool_bulkcopycourse_jobs',
            'id',
            "userid $insql",
            $params
        );

        if (!empty($jobids)) {
            list($jobinsql, $jobparams) = $DB->get_in_or_equal($jobids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('tool_bulkcopycourse_items', "jobid $jobinsql", $jobparams);
            $DB->delete_records_select('tool_bulkcopycourse_jobs', "userid $insql", $params);
        }
    }
}

