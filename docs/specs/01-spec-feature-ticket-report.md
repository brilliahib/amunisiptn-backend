# Feature Spec: Ticket Report

## Overview

Feature **Ticket Report** memungkinkan user aplikasi tryout **AmunisiPTN** untuk melaporkan bug, error, atau kendala selama menggunakan sistem.

Tujuan feature ini:

- Menyediakan jalur komunikasi terstruktur antara user dan admin terkait masalah teknis.
- Mempermudah tracking issue hingga selesai.
- Meningkatkan user experience dengan transparansi progress penyelesaian.

---

# Goals

- User dapat membuat laporan kendala.
- User dapat melihat progress/status ticket.
- Admin dapat memonitor semua ticket.
- Admin dapat mengubah status ticket.

---

# Actors

## User

Role yang membuat ticket report.

Permissions:

- Create ticket
- View own tickets
- Upload supporting images
- Monitor ticket status

---

## Admin

Role yang menangani ticket report.

Permissions:

- View all tickets
- Filter tickets by status
- Update ticket status
- Resolve ticket

---

# Ticket Lifecycle

```text
OPEN → IN_PROGRESS → SOLVED
```

## Status Definition

### OPEN

Ticket baru dibuat dan belum ditangani.

### IN_PROGRESS

Admin sedang investigasi atau menangani.

### SOLVED

Masalah telah diselesaikan.

---

# User Flow

## Create Ticket

User membuka halaman:

```text
Dashboard → Help Center → Report Issue
```

User mengisi form:

### Fields

| Field       | Type                  | Required |
| ----------- | --------------------- | -------- |
| title       | string                | yes      |
| description | rich text             | yes      |
| images      | multiple image upload | no       |

System automatically sets:

```text
status = OPEN
created_by = current_user
created_at = current_timestamp
```

---

## View My Tickets

User dapat melihat daftar ticket miliknya.

Displayed fields:

- Ticket ID
- Title
- Current Status
- Created At
- Updated At

Actions:

- View detail ticket

---

## Ticket Detail

User dapat melihat:

- Title
- Description
- Uploaded images
- Current status
- Created at
- Updated at

---

# Admin Flow

## Ticket Dashboard

Admin dapat melihat seluruh ticket.

Features:

- Search by title
- Filter by status
- Sort by latest
- View ticket detail

---

## Update Ticket Status

Admin dapat mengubah status:

```text
OPEN → IN_PROGRESS
IN_PROGRESS → SOLVED
```

---

# Functional Requirements

## FR-01 Create Ticket

System harus mengizinkan user yang sudah login untuk membuat ticket.

Validation:

- title required
- description required

---

## FR-02 Upload Images

System harus mendukung upload multiple images secara opsional.

Constraints:

- max 5 images
- max 3MB per image
- allowed formats: jpg, jpeg, png, webp

Storage:

```text
/storage/ticket-reports/
```

Data disimpan dalam bentuk array/json pada field `images`.

---

## FR-03 Rich Text Description

Description harus support:

- bold
- italic
- bullet list
- ordered list
- code block
- links

Suggested editor:

- Tiptap
- Quill
- CKEditor

---

## FR-04 Ticket Listing

User hanya dapat melihat ticket miliknya sendiri.

Admin dapat melihat semua ticket.

---

## FR-05 Ticket Status Management

Admin dapat mengubah status ticket.

Valid status:

- OPEN
- IN_PROGRESS
- SOLVED

Status transition harus valid.

---

# Non Functional Requirements

## Performance

- Ticket list load under 2 seconds
- Image upload must show progress indicator

---

## Security

- Only authenticated users can create ticket
- User cannot access other users’ ticket
- Only admin can update status

---

## Scalability

System should support:

- 10.000+ tickets
- optimized image storage

---

# Database Design

## ticket_reports

| Field       | Type          |
| ----------- | ------------- |
| id          | uuid          |
| user_id     | uuid          |
| title       | varchar       |
| description | longtext      |
| images      | json nullable |
| status      | enum          |
| created_at  | timestamp     |
| updated_at  | timestamp     |

---

# API Contract

## Create Ticket

```http
POST /api/ticket-reports
```

Payload:

```json
{
    "title": "Timer ujian berhenti",
    "description": "<p>Timer tidak berjalan saat pindah soal</p>",
    "images": ["file"]
}
```

Response:

```json
{
    "success": true,
    "data": {}
}
```

---

## Get User Tickets

```http
GET /api/ticket-reports
```

---

## Get Ticket Detail

```http
GET /api/ticket-reports/{id}
```

---

## Admin Get All Tickets

```http
GET /api/admin/ticket-reports
```

Query:

```text
?status=OPEN
```

---

## Update Ticket Status

```http
PATCH /api/admin/ticket-reports/{id}/status
```

Payload:

```json
{
    "status": "IN_PROGRESS"
}
```

---

# UI Components

## User Side

- TicketReportButton
- CreateTicketDialog
- TicketReportForm
- TicketReportList
- TicketReportCard
- TicketReportDetail

---

## Admin Side

- TicketReportTable
- TicketStatusBadge
- TicketFilterTabs
- TicketDetailDrawer
- UpdateStatusDialog

---

# Edge Cases

## Empty Images

Ticket tetap bisa dibuat tanpa image.

---

## Large Images

System harus reject jika image melebihi batas.

---

## Deleted User

Ticket tetap tersimpan meskipun user dihapus.

---

# Future Improvements

- Comment thread antara user dan admin
- Re-open solved ticket
- Priority labels (Low, Medium, High, Critical)
- Category tags (Bug, Payment, UI, System Error)
- Attachment file selain image
- Notification system

---

# Acceptance Criteria

- User dapat submit ticket report
- User dapat melihat daftar ticket miliknya
- User dapat melihat detail ticket
- Admin dapat melihat seluruh ticket
- Admin dapat filter ticket berdasarkan status
- Admin dapat update status ticket
- Upload image berjalan dengan baik
- Access control berjalan sesuai role
- Rich text description tersimpan dengan benar
