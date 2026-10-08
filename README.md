# Moodle Plugin: Bulk Copy Courses (`tool_bulkcopycourse`)

[![Moodle Plugin CI](https://github.com/ferdiyantodeni/moodle-tool_bulkcopycourse/actions/workflows/ci.yml/badge.svg)](https://github.com/ferdiyantodeni/moodle-tool_bulkcopycourse/actions)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE.md)

An administrator tool for **Moodle 4.2+** to **massively copy and duplicate courses in bulk** from master/template courses using a CSV file.

This plugin copies all course contents, modules, sections, activities, blocks, and filters, while ensuring a **100% clean copy** without copying old participants, submissions, grades, or user activity logs.

---

## ✨ Features

- **Massive Batch Course Duplication:** Clone tens or hundreds of courses from source template courses in a single batch.
- **Clean Copy Guarantee:** Excludes user data, role assignments, student submissions, grades, and forum posts.
- **Dynamic Column Mapping:** Flexible CSV columns. Headers can be in any order.
- **Smart Date Parser:** Supports ISO (`YYYY-MM-DD`), standard formats (`DD/MM/YYYY`, `DD-MM-YYYY`), 12-hour AM/PM, Indonesian month names (*"06 Oktober 2026"*), and Excel date serial numbers.
- **Dual Execution Modes:**
  - **Live AJAX Progress Bar:** Watch real-time row-by-row course duplication in the browser.
  - **Background Processing:** Queue the batch into a Moodle Ad-hoc Task processed by cron.
- **History & CSV Export:** Full audit trail with links to newly created courses, error messages, and exportable CSV history.
- **Fully GDPR / Moodle Privacy API Compliant.**

---

## 📋 CSV Format & Columns

The first line (header) of the CSV file must contain the column names. Header names are case-insensitive and can be placed in any column order:

| Column | Type | Required? | Description | Example |
| :--- | :--- | :--- | :--- | :--- |
| `category` | Integer | **Yes** | Destination course category ID | `6`, `20`, `78` |
| `copyshortname` | String | **Yes** | Shortname of the source/template course | `CLP FRM 06/08/2026-07/08/2026` |
| `fullname` | String | **Yes** | Full name of the new course | `CLP for FRM/JS 06-07 October 2026` |
| `shortname` | String | **Yes** | Shortname of the new course (must be unique) | `CLP FRM 06/10/2026-07/10/2026` |
| `startdate` | Date/String | Optional | Course start date/time | `06-10-2026 00:00:00` |
| `enddate` | Date/String | Optional | Course end date/time | `07-10-2026 23:59:59` |
| `enrols` | String | Optional | Enrolment methods to enable (e.g., `manual`) | `manual` |
| `visible` | Integer (0/1) | Optional | Course visibility (1 = show, 0 = hide) | `1` |
| `idnumber` | String | Optional | Course ID number or unique code | `2026-CLP-23` |

A sample CSV file is provided in [`sample_courses.csv`](sample_courses.csv).

---

## 🛠️ Installation

### Via Moodle Plugins Directory (Recommended)
1. Log in to your Moodle site as administrator.
2. Go to **Site administration > Plugins > Install plugins**.
3. Search for **Bulk Copy Courses** (`tool_bulkcopycourse`) or upload the ZIP package.
4. Follow the on-screen instructions to complete the installation.

### Manual Installation (Git)
Clone or copy this repository into your Moodle's `admin/tool` directory:
```bash
cd /path/to/moodle/admin/tool
git clone https://github.com/ferdiyantodeni/moodle-tool_bulkcopycourse.git bulkcopycourse
```
Then visit **Site administration > Notifications** to trigger the database upgrade.

---

## 🚀 Usage

1. Navigate to **Site administration > Courses > Bulk Copy Courses**.
2. Download the sample CSV template or prepare your CSV file.
3. Upload your CSV and choose delimiter/encoding.
4. Review the **Interactive Preview Table**:
   - Verify category IDs and source course shortnames.
   - Rows with errors or duplicate shortnames are highlighted.
5. Choose your execution mode:
   - Click **Start Copying Courses** for real-time live browser execution.
   - Click **Queue in Background (Ad-hoc Task)** for off-peak cron processing.
6. Check results in the **Job History** table and download the CSV report.

---

## ⚙️ Requirements

- Moodle 4.2 or higher (supported up to Moodle 4.5+).
- PHP 8.1 or PHP 8.2+.
- Capability: `tool/bulkcopycourse:bulkcopy` (allowed for Managers and Site Administrators by default).

---

## 📄 License & Author

- **Author / Developer:** Ferdiyanto Deni Sanjaya
- **Website:** [https://gridiyans.my.id](https://gridiyans.my.id)
- **License:** GNU General Public License v3.0 or later ([LICENSE.md](LICENSE.md)).

