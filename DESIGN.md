# TraceOn — Design System & UI/UX Specifications (DESIGN.md)

**Jenis Dokumen:** Single Source of Truth — Visual Design System & UI/UX Guidelines  
**Proyek:** TraceOn — Web-based workspace & task monitoring system  
**Penyelarasan Stack:** Pure Native CSS Custom Properties (Tanpa CSS Framework), Native HTML5 Semantic Elements, dan Vanilla Javascript ES Modules.  
**Baseline Kepatuhan:** Standar Kontras WCAG 2.1 Level AA & AAA untuk legibilitas teks yang tinggi.

## 1. Design Tokens & Core System (Atomics)

Semua token visual wajib dideklarasikan di tingkat global menggunakan CSS Custom Properties di dalam selector `:root` (misal pada `app.css`). Penggunaan nilai warna atau ukuran yang di-*hardcode* secara langsung pada class komponen sangat dilarang.

### 1.1 Color Palette & Contrast Tokens

```css
:root {  
    /* Base Color Tokens */  
    --color-primary: #1A3A5C;      /* Deep Navy: Sidebar BG, Heading utama, teks kontras tinggi (AAA - 10.9:1) */  
    --color-secondary: #2175B8;    /* Medium Blue: Primary Button, Navigasi Active State, Links (AA - 4.6:1) */  
    --color-accent: #4BA3E3;       /* Sky Blue: Badge border, hover indicators, focus rings (AA - 4.7:1) */  
    --color-surface: #F4F7FB;      /* Soft Greyish Blue: Card BG, Hover state list, Modal backdrop */  
    --color-background: #FFFFFF;   /* Pure White: Base Page Layout, Card Item, Input BG */  
    --color-border: #E8EDF3;       /* Soft Gray: Dividers, Input border default (Non-text element) */  
  
    /* Semantic Feedback Tokens */  
    --color-success: #2E7D32;      /* Forest Green: Progress 100%, Approve Badge, Success Toast (AA - 4.6:1) */  
    --color-warning: #E65100;      /* Deep Orange: Deadline < 3 hari, Warning Toast (AA - 4.8:1) */  
    --color-error: #C62828;        /* Crimson Red: Danger Zone, Aksi Hapus, Reject Toast (AA - 5.7:1) */  
    --color-info: #1565C0;         /* Cobalt Blue: System Log Indicator, Info Toast (AA - 5.6:1) */  
  
    /* Typography Text Colors */  
    --text-primary: #1A202C;       /* Charcoal: Body Text, subheadings (AAA - 14.8:1) */  
    --text-muted: #4A5568;         /* Slate Gray: Captions, placeholders, relative timestamps (AA - 6.3:1) */  
}  
```

### 1.2 Typography Scale (Major Third Ratio)

Aplikasi menggunakan kombinasi dua font modern untuk menjamin estetika geometris sekaligus legibilitas tinggi pada resolusi rendah:
- **Font Heading:** Plus Jakarta Sans, sans-serif (Untuk penegasan visual berkarakter geometris).
- **Font Body:** Inter, sans-serif (Netral, keterbacaan tinggi).

```css
:root {  
    /* Font Families */  
    --font-heading: "Plus Jakarta Sans", "Inter", system-ui, sans-serif;  
    --font-body: "Inter", system-ui, sans-serif;  
  
    /* Heading Sizes & Weights (Plus Jakarta Sans) */  
    --fs-h1: 24px;   --lh-h1: 32px;  --fw-h1: 700; /* Nama Workspace Utama */  
    --fs-h2: 20px;   --lh-h2: 28px;  --fw-h2: 600; /* Judul Card Detail / Section */  
    --fs-h3: 16px;   --lh-h3: 24px;  --fw-h3: 600; /* Judul Card Grid / Pop-up Title */  
  
    /* Body & Utility Sizes & Weights (Inter) */  
    --fs-body: 14px; --lh-body: 20px; --fw-body: 400; /* Teks Deskripsi / Item Todo */  
    --fs-btn: 14px;  --lh-btn: 20px;  --fw-btn: 600;  /* Label Tombol / Interaktif */  
    --fs-sm: 12px;   --lh-sm: 16px;   --fw-sm: 500;   /* Meta log, timestamp, badge */  
}  
```

### 1.3 Spacing Grid System (8px Base Grid)

Sistem tata letak dan penentuan jarak komponen didasarkan pada kelipatan kelipatan 8px.
- `--space-2xs`: 4px (Jarak antara teks utama dan helper-text di dalam form).
- `--space-xs`: 8px (Jarak antarelemen di dalam list Todo, padding komponen badge).
- `--space-sm`: 12px (Padding tombol standar, jarak internal card header).
- `--space-md`: 16px (Jarak standard margin, padding default input field).
- `--space-lg`: 24px (Padding internal Card Workspace, padding modal desktop).
- `--space-xl`: 32px (Jarak vertikal antar-section dashboard utama).

### 1.4 Border Radius & Elevation (Shadows)

```css
:root {  
    /* Radius */  
    --radius-sm: 4px;   /* Checkbox, badge tag, small dropdown indicators */  
    --radius-md: 8px;   /* Default Button, input text field, custom context menu */  
    --radius-lg: 12px;  /* Workspace Card Grid, Modal Dialog, Floating Toast */  
  
    /* Shadows */  
    --shadow-sm: 0 1px 3px rgba(26, 58, 92, 0.05), 0 1px 2px rgba(26, 58, 92, 0.03); /* Default Card */  
    --shadow-md: 0 4px 6px -1px rgba(26, 58, 92, 0.08), 0 2px 4px -1px rgba(26, 58, 92, 0.04); /* Hover Card / Sticky Nav */  
    --shadow-lg: 0 10px 15px -3px rgba(26, 58, 92, 0.12), 0 4px 6px -2px rgba(26, 58, 92, 0.05); /* Modals, Toasts, Dropdowns */  
}  
```

## 2. Component Library Specifications (State Matrix)

Setiap komponen UI wajib mengimplementasikan seluruh kondisi (states) secara konsisten menggunakan transisi CSS native.

### 2.1 Buttons

**A. Tombol Utama (`.btn-primary`)**
- **Default State:** Background `--color-secondary` (#2175B8), Teks #FFFFFF, border 1px solid transparent.
- **Hover State:** Background `--color-accent` (#4BA3E3), cursor pointer, transisi background-color 0.2s ease.
- **Focus State:** Border `--color-primary`, outline: none, box-shadow: 0 0 0 3px rgba(75, 163, 227, 0.4).
- **Active State:** Background #1565C0 (biru gelap), transform: scale(0.98) dengan transisi cepat 0.1s.
- **Disabled State:** Background `--color-border` (#E8EDF3), teks `--text-muted`, cursor: not-allowed, opacity: 0.6.

**B. Tombol Bahaya/Destruktif (`.btn-danger`)**
- **Default State:** Background `--color-error` (#C62828), Teks #FFFFFF, border 1px solid transparent.
- **Hover State:** Background #D32F2F, cursor pointer, transisi 0.2s.
- **Focus State:** outline: none, box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.4).
- **Active State:** Background #B71C1C, transform: scale(0.98).
- **Disabled State:** Background --color-border, teks --text-muted, opacity: 0.6, cursor: not-allowed.

### 2.2 Form Inputs & Interactive Controls

**A. Input Teks & Tanggal (`.form-control`)**
- **Default State:** Background #FFFFFF, Border 1.5px solid var(--color-border), Teks `--text-primary`, Radius `--radius-md`.
- **Hover State:** Border `--color-secondary` (#2175B8).
- **Focus State:** Border `--color-accent`, outline: none, box-shadow: 0 0 0 3px rgba(75, 163, 227, 0.2).
- **Disabled State:** Background `--color-surface`, Teks `--text-muted`, cursor: not-allowed, opacity: 0.7.
- **Error State:** Border `--color-error` (#C62828). Wajib merender pesan error inline di bawah kolom dengan font-size 12px, warna `--color-error`, disertai ikon peringatan mini.

### 2.3 Progress Bar Component

- **Outer Track (`.progress-track`):** Tinggi 8px, background `--color-border`, radius `--radius-sm`, overflow: hidden.
- **Inner Fill (`.progress-bar-fill`):** Tinggi 100%, background `--color-secondary`, transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1).
- **Success Integration:** Jika JavaScript menghitung progres tugas mencapai 100%, kelas `.bg-success` wajib ditambahkan secara dinamis untuk mengubah warna fill menjadi `--color-success` (#2E7D32).

## 3. Information Architecture & Layout Wireframes

### 3.1 Struktur Antarmuka Pasca-Login (Split-Screen Layout)

```text
[DASHBOARD WORKSPACE (Internal split-screen layout)]  
 ├── SIDEBAR (Kiri - Persisten, Width: 260px)  
 │    ├── Logo TraceOn  
 │    ├── Tombol "+ Workspace Baru" (Memicu Modal Workspace Baru)  
 │    ├── Tombol "Join Workspace" (Memicu Modal Input Invite Code)  
 │    ├── Accordion "Dibagikan" -> List Workspace ter-approve oleh Owner lain  
 │    ├── Accordion "Workspace Pribadi" -> List Workspace milik sendiri  
 │    └── Profile Footer (Avatar, Nama User, Tombol Profile)  
 └── CONTAINER UTAMA (Kanan - Berorientasi Scroll, Flexible Width)  
      ├── Header: Breadcrumb Navigasi, Status Ringkasan Workspace % Progres  
      ├── Tabs Navigasi Internal (Dashboard, Anggota, Log Aktivitas)  
      │    ├── Tab Dashboard: Grid Card Utama Tugas/Card Kerja  
      │    ├── Tab Anggota: Tabel Pengaturan Wewenang Anggota (Owner/Admin/Member)  
      │    └── Tab Log Aktivitas: Search & Filter Panel, Feed Server-Side Rendered Logs  
      └── Footer: Copyright & Status Koneksi Database  
```

## 4. Micro-interactions & Animations

Seluruh interaksi transisi menggunakan CSS native berkinerja tinggi yang berjalan pada GPU compositor thread untuk mencegah layout shift.

### 4.1 CSS Keyframes Definitions

```css
/* 1. Shimmer Skeleton Loading Effect */  
@keyframes shimmer {  
  0% { background-position: -450px 0; }  
  100% { background-position: 450px 0; }  
}  
.skeleton-shimmer {  
  background: linear-gradient(to right, #E8EDF3 8%, #F4F7FB 18%, #E8EDF3 33%);  
  background-size: 800px 104px;  
  animation: shimmer 1.5s infinite linear;  
}  
  
/* 2. Toast Entry (Slide In dari Kanan) */  
@keyframes toastSlideIn {  
  0% { transform: translateX(120%) translateY(0); opacity: 0; }  
  100% { transform: translateX(0) translateY(0); opacity: 1; }  
}  
.toast-animate-in {  
  animation: toastSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;  
}  
```

### 4.2 Aturan Perilaku Interaksi

1. **Sidebar Collapse Transition:** Saat sidebar ditutup ke Mini Mode, lebar berubah dari 260px ke 72px. Transisi wajib menggunakan properti: `transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);`. Teks dan label menu di dalamnya wajib ditransisikan menggunakan `transition: opacity 0.15s ease-out;` untuk menghindari pembungkusan teks (text-wrapping) yang merusak visual selama proses kolaps berjalan.
2. **Todo Item Status Transition:** Ketika todo dicentang menjadi selesai, teks todo akan mengecil sebanyak 10% dan memicu efek coretan garis (strikethrough) menggunakan `transition: text-decoration 0.2s, opacity 0.25s ease-out;` disertai perubahan opacity container baris yang meredup dari 1 menjadi 0.6.
3. **Honeypot Anti-Spam Form Security UI:** Input field rahasia untuk menangkap bot otomatis disembunyikan menggunakan posisi absolut di luar area pandang (`position: absolute; left: -9999px;`) serta ditandai dengan properti `aria-hidden="true"` dan `tabindex="-1"`.
