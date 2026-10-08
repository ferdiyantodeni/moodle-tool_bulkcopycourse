<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Salin Kursus Massal (Bulk Copy Courses)';
$string['bulkcopycourse:bulkcopy'] = 'Salin kursus secara massal dari template';
$string['uploadfile'] = 'File CSV Kursus';
$string['uploadfile_help'] = 'Unggah file CSV yang berisi daftar kursus yang ingin disalin. File harus memuat kolom: category, copyshortname, fullname, startdate, enddate, shortname, enrols, visible, idnumber.';
$string['delimiter'] = 'Pemisah CSV (Delimiter)';
$string['encoding'] = 'Encoding File';
$string['previewtitle'] = 'Pratinjau Salin Kursus Massal';
$string['previewdesc'] = 'Silakan periksa hasil validasi kursus di bawah ini sebelum melanjutkan proses penyalinan.';
$string['startcopy'] = 'Mulai Salin Kursus';
$string['startcopy_background'] = 'Jalankan di Background (Ad-hoc Task)';
$string['download_sample'] = 'Unduh Contoh Template CSV';
$string['status_ready'] = 'Siap';
$string['status_warning'] = 'Peringatan';
$string['status_error'] = 'Error';
$string['status_success'] = 'Berhasil';
$string['status_processing'] = 'Memproses';
$string['status_pending'] = 'Menunggu';
$string['status_failed'] = 'Gagal';

// Kolom Tabel
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

// Pemrosesan
$string['processing_title'] = 'Memproses Salin Kursus Massal';
$string['processing_desc'] = 'Proses penyalinan sedang berjalan. Mohon jangan menutup halaman ini sampai selesai.';
$string['copy_in_progress'] = 'Sedang menyalin: {$a}';
$string['copy_success'] = 'Berhasil membuat kursus: {$a}';
$string['copy_failed'] = 'Gagal menyalin: {$a}';
$string['all_completed'] = 'Proses salin massal selesai!';
$string['view_course'] = 'Lihat Kursus';
$string['view_history'] = 'Lihat Riwayat Salin';
$string['back_to_upload'] = 'Kembali ke Unggah';
$string['total_courses'] = 'Total Kursus: {$a}';
$string['completed_courses'] = 'Selesai: {$a}';
$string['failed_courses'] = 'Gagal: {$a}';
$string['recent_jobs'] = 'Riwayat Pekerjaan Terbaru';
$string['no_jobs_found'] = 'Belum ada riwayat pekerjaan.';
$string['job_id'] = 'Pekerjaan #{$a}';
$string['created_date'] = 'Tanggal Dibuat';
$string['confirm_start'] = 'Apakah Anda yakin ingin menyalin {$a} kursus ini?';
$string['task_process_bulk_copy'] = 'Pemroses Salin Kursus Massal di Latar Belakang';
$string['history'] = 'Riwayat Pekerjaan';
$string['privacy:metadata:jobs'] = 'Informasi tentang pekerjaan salin kursus massal yang diinisiasi oleh pengguna.';
$string['privacy:metadata:jobs:userid'] = 'ID pengguna yang menginisiasi pekerjaan salin massal.';
$string['privacy:metadata:jobs:name'] = 'Nama pekerjaan salin kursus massal.';
$string['privacy:metadata:jobs:status'] = 'Status eksekusi pekerjaan.';
$string['privacy:metadata:jobs:totalcourses'] = 'Jumlah total kursus dalam pekerjaan.';
$string['privacy:metadata:jobs:timecreated'] = 'Waktu pembuatan pekerjaan.';
$string['privacy:metadata:jobs:timemodified'] = 'Waktu terakhir pekerjaan diperbarui.';

