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
 * Indonesian language strings for tool_bulkcopycourse.
 *
 * @package    tool_bulkcopycourse
 * @copyright  2024 Ferdiyan Deni <ferdiyantodeni@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Salin Kursus Massal (Bulk Copy Courses)';
$string['plugin_subtitle'] = 'Salin kursus baru secara massal dari template master (tanpa menyertakan data peserta lama).';
$string['bulkcopycourse:bulkcopy'] = 'Salin kursus secara massal dari template';
$string['uploadfile'] = 'File CSV Kursus';
$string['uploadfile_help'] = 'Unggah file CSV yang berisi daftar kursus yang ingin disalin. File harus memuat kolom: category, copyshortname, fullname, startdate, enddate, shortname, enrols, visible, idnumber.';
$string['delimiter'] = 'Pemisah CSV (Delimiter)';
$string['encoding'] = 'Encoding File';
$string['autodetect'] = 'Deteksi Otomatis';
$string['comma'] = 'Koma (,)';
$string['semicolon'] = 'Titik koma (;)';
$string['tab'] = 'Tab (\\t)';
$string['previewtitle'] = 'Pratinjau Salin Kursus Massal';
$string['previewdesc'] = 'Silakan periksa hasil validasi kursus di bawah ini sebelum melanjutkan proses penyalinan.';
$string['startcopy'] = 'Mulai Salin Kursus';
$string['startcopy_live'] = 'Mulai Salin Kursus (Langsung dengan Progress Bar)';
$string['startcopy_background'] = 'Jalankan di Background (Ad-hoc Task)';
$string['download_sample'] = 'Unduh Contoh Template CSV';
$string['status_ready'] = 'Siap';
$string['status_warning'] = 'Peringatan';
$string['status_error'] = 'Error';
$string['status_success'] = 'Berhasil';
$string['status_processing'] = 'Memproses';
$string['status_pending'] = 'Menunggu';
$string['status_failed'] = 'Gagal';
$string['status_queued'] = 'Dalam Antrean';

// Kolom Tabel & Header CSV
$string['col_linenumber'] = 'Baris';
$string['col_copyshortname'] = 'Kursus Sumber (copyshortname)';
$string['col_fullname'] = 'Nama Lengkap Baru';
$string['col_shortname'] = 'Shortname Baru';
$string['col_category'] = 'ID Kategori';
$string['col_dates'] = 'Tgl Mulai / Selesai';
$string['col_visible'] = 'Visibilitas';
$string['col_idnumber'] = 'Nomor ID';
$string['col_enrols'] = 'Pendaftaran';
$string['col_status'] = 'Status Validasi';
$string['col_actions'] = 'Aksi';
$string['col_totalcourses'] = 'Total Kursus';
$string['header_jobid'] = 'Job ID';
$string['header_jobname'] = 'Nama Pekerjaan';
$string['header_totalcourses'] = 'Total Kursus';
$string['created_by'] = 'Dibuat Oleh';
$string['created_date'] = 'Tanggal Dibuat';
$string['new_course_id'] = 'ID Kursus Baru';
$string['new_course_url'] = 'URL Kursus Baru';
$string['error_or_message'] = 'Hasil / Error';
$string['course_link'] = 'Tautan Kursus';
$string['course_list_in_job'] = 'Daftar Kursus dalam Pekerjaan Ini';

// Kotak Informasi CSV
$string['csv_format_title'] = 'Format Kolom File CSV';
$string['csv_format_desc'] = 'File CSV yang diunggah harus memiliki format header baris pertama persis seperti tabel berikut:';
$string['col_desc_category'] = 'ID Kategori tujuan penempatan kursus baru';
$string['col_desc_copyshortname'] = 'Shortname kursus master yang ingin disalin';
$string['col_desc_fullname'] = 'Nama lengkap untuk kursus baru';
$string['col_desc_shortname'] = 'Shortname unik untuk kursus baru';
$string['col_desc_startdate'] = 'Tanggal mulai kursus (cth. 06-10-2026 00:00:00 atau 2026-10-06)';
$string['col_desc_enddate'] = 'Tanggal selesai kursus (cth. 07-10-2026 23:00:00 atau 2026-10-07)';
$string['col_desc_enrols'] = 'Metode pendaftaran (manual, self, dll)';
$string['col_desc_visible'] = 'Visibilitas kursus (1 = tampil, 0 = sembunyikan)';
$string['col_desc_idnumber'] = 'Nomor identifikasi kursus';
$string['required'] = 'Wajib';
$string['optional'] = 'Opsional';

// Pesan Validasi
$string['err_filenotfound'] = 'File yang diunggah tidak dapat dibaca.';
$string['err_missingcolumns'] = 'Kolom wajib tidak ditemukan: {$a}';
$string['err_sourcecoursenotfound'] = 'Kursus sumber dengan shortname "{$a}" tidak ditemukan.';
$string['err_categorynotfound'] = 'Kategori dengan ID {$a} tidak ditemukan.';
$string['err_shortnametaken'] = 'Shortname "{$a}" sudah digunakan oleh kursus lain.';
$string['err_shortnameempty'] = 'Shortname tidak boleh kosong.';
$string['err_fullnameempty'] = 'Fullname tidak boleh kosong.';
$string['err_duplicateinbatch'] = 'Ditemukan duplikasi shortname "{$a}" di dalam file ini.';
$string['err_invaliddate'] = 'Format tanggal tidak valid.';
$string['err_enddate_before_startdate'] = 'Tanggal selesai mendahului tanggal mulai (enddate < startdate)';
$string['err_cannotcreatecourse'] = 'Tidak dapat membuat record kursus tujuan.';
$string['err_itemnotfound'] = 'Item antrean tidak ditemukan.';

// Pemrosesan
$string['processing_title'] = 'Memproses Salin Kursus Massal';
$string['processing_desc'] = 'Proses penyalinan sedang berjalan. Mohon jangan menutup halaman ini sampai selesai.';
$string['copy_in_progress'] = 'Sedang menyalin: {$a}';
$string['copy_success'] = 'Berhasil membuat kursus: {$a}';
$string['copy_failed'] = 'Gagal menyalin: {$a}';
$string['all_completed'] = 'Proses salin massal selesai!';
$string['view_course'] = 'Buka Kursus';
$string['view_history'] = 'Lihat Riwayat Salin';
$string['back_to_upload'] = 'Kembali ke Unggah';
$string['total_courses'] = 'Total Kursus: {$a}';
$string['completed_courses'] = 'Selesai: {$a}';
$string['failed_courses'] = 'Gagal: {$a}';
$string['queue_remaining'] = 'Sisa Antrean';
$string['recent_jobs'] = 'Riwayat Pekerjaan Terbaru';
$string['no_jobs_found'] = 'Belum ada riwayat pekerjaan.';
$string['job_id'] = 'Pekerjaan #{$a}';
$string['confirm_start'] = 'Apakah Anda yakin ingin menyalin {$a} kursus ini?';
$string['task_process_bulk_copy'] = 'Pemroses Salin Kursus Massal di Latar Belakang';
$string['history'] = 'Riwayat Pekerjaan';
$string['history_list'] = 'Daftar Riwayat';
$string['view_results_report'] = 'Lihat Laporan & Hasil Salin';
$string['copy_more_courses'] = 'Salin Kursus Lainnya';
$string['download_results_csv'] = 'Unduh Hasil (CSV)';
$string['download_all_csv'] = 'Unduh Rekap Semua (CSV)';
$string['delete_job'] = 'Hapus Pekerjaan Ini';
$string['confirm_delete_job'] = 'Apakah Anda yakin ingin menghapus riwayat pekerjaan #{$a}?';
$string['job_deleted_success'] = 'Riwayat pekerjaan #{$a} berhasil dihapus.';
$string['cancel_job'] = 'Batalkan / Hapus';
$string['confirm_cancel_job'] = 'Yakin ingin membatalkan pekerjaan ini?';
$string['job_cancelled_success'] = 'Pekerjaan berhasil dibatalkan.';
$string['ready_to_copy'] = 'Siap Disalin (Ready)';
$string['error_invalid'] = 'Error / Tidak Valid';
$string['job_already_queued'] = 'Pekerjaan ini sudah berada dalam antrean atau sedang diproses.';
$string['job_queued_success'] = 'Pekerjaan salin kursus massal telah berhasil dimasukkan ke antrean background (Ad-hoc Task).';
$string['job_in_queue_desc'] = 'Pekerjaan ini sudah berada di antrean background (Ad-hoc Task). Cron Moodle akan mengeksekusi penyalinan kursus ini secara otomatis di latar belakang.';
$string['job_in_progress_desc'] = 'Pekerjaan ini sedang dalam proses penyalinan.';
$string['view_queue_status'] = 'Lihat Status Antrean & Hasil';
$string['warning_errors_in_batch'] = 'Perhatian: Terdapat {$a} baris kursus yang memiliki error. Baris yang memiliki status ERROR akan dilewati secara otomatis saat proses salin dijalankan.';
$string['queueing_in_progress'] = 'Memasukkan ke antrean...';
$string['live_log_title'] = 'Log Proses Salin Real-time:';
$string['preparing_log'] = '[{$a}] Memulai persiapan proses salin kursus massal...';
$string['preparing_items'] = 'Mempersiapkan {$a} kursus untuk disalin...';
$string['copying_line'] = 'Menyalin Baris {$a->line}: dari master [{$a->source}] ke [{$a->target}]...';
$string['copy_success_detail'] = 'Berhasil membuat kursus ID: {$a->id} ({$a->shortname})';
$string['copy_failed_detail'] = 'Gagal menyalin Baris {$a->line} ({$a->shortname}): {$a->error}';
$string['network_error'] = 'Error jaringan/server pada Baris {$a->line}: {$a->error}';
$string['no_pending_courses'] = 'Tidak ada kursus berstatus pending yang perlu diproses.';
$string['version_info'] = 'Versi {$a->version} • Dikembangkan oleh {$a->author}';

// Privacy API
$string['privacy:metadata:jobs'] = 'Informasi tentang pekerjaan salin kursus massal yang diinisiasi oleh pengguna.';
$string['privacy:metadata:jobs:userid'] = 'ID pengguna yang menginisiasi pekerjaan salin massal.';
$string['privacy:metadata:jobs:name'] = 'Nama pekerjaan salin kursus massal.';
$string['privacy:metadata:jobs:status'] = 'Status eksekusi pekerjaan.';
$string['privacy:metadata:jobs:totalcourses'] = 'Jumlah total kursus dalam pekerjaan.';
$string['privacy:metadata:jobs:timecreated'] = 'Waktu pembuatan pekerjaan.';
$string['privacy:metadata:jobs:timemodified'] = 'Waktu terakhir pekerjaan diperbarui.';
