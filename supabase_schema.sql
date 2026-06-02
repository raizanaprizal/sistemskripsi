-- ============================================================
-- supabase_schema.sql
-- Schema Database: Sistem Pemilihan Vendor PT Bio Farma (Persero)
-- Database: PostgreSQL (Supabase)
--
-- CARA PENGGUNAAN:
-- 1. Buka Supabase Dashboard → SQL Editor
-- 2. Paste seluruh isi file ini
-- 3. Klik tombol "Run"
-- ============================================================

-- ── 1. Tabel sessions (untuk PHP session handler) ────────────
CREATE TABLE IF NOT EXISTS sessions (
    id          VARCHAR(128)  NOT NULL PRIMARY KEY,
    data        TEXT          NOT NULL DEFAULT '',
    last_activity TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── 2. Tabel users ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id_user      SERIAL        PRIMARY KEY,
    username     VARCHAR(50)   NOT NULL UNIQUE,
    password     VARCHAR(255)  NOT NULL,
    nama_lengkap VARCHAR(100)  NOT NULL,
    role         VARCHAR(20)   NOT NULL CHECK (role IN ('Administrator','Staff','Manajer')),
    status_aktif BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- ── 3. Tabel vendor ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS vendor (
    id_vendor    SERIAL        PRIMARY KEY,
    kode_vendor  VARCHAR(20)   NOT NULL UNIQUE,
    nama_vendor  VARCHAR(150)  NOT NULL,
    keterangan   VARCHAR(10)   NOT NULL CHECK (keterangan IN ('Lokal','Impor')),
    alamat       TEXT,
    no_telepon   VARCHAR(20),
    email        VARCHAR(100),
    status_aktif BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- ── 4. Tabel kriteria ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS kriteria (
    id_kriteria    SERIAL        PRIMARY KEY,
    kode_kriteria  VARCHAR(20)   NOT NULL UNIQUE,
    nama_kriteria  VARCHAR(100)  NOT NULL,
    bobot          NUMERIC(5,4)  NOT NULL DEFAULT 0,
    jenis_kriteria VARCHAR(10)   NOT NULL CHECK (jenis_kriteria IN ('benefit','cost')),
    nilai_min      NUMERIC(10,2) NOT NULL DEFAULT 0,
    nilai_max      NUMERIC(10,2) NOT NULL DEFAULT 100,
    keterangan     TEXT
);

-- ── 5. Tabel jenis_barang ────────────────────────────────────
CREATE TABLE IF NOT EXISTS jenis_barang (
    id_jenis       SERIAL        PRIMARY KEY,
    kode_barang    VARCHAR(20)   NOT NULL UNIQUE,
    nama_barang    VARCHAR(150)  NOT NULL,
    kategori_jenis VARCHAR(100),
    satuan         VARCHAR(50),
    keterangan     TEXT,
    status_aktif   BOOLEAN       NOT NULL DEFAULT TRUE
);

-- ── 6. Tabel periode ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS periode (
    id_periode   SERIAL        PRIMARY KEY,
    tahun        VARCHAR(4)    NOT NULL,
    semester     VARCHAR(10)   NOT NULL CHECK (semester IN ('Ganjil','Genap')),
    keterangan   TEXT,
    status_aktif BOOLEAN       NOT NULL DEFAULT FALSE,
    UNIQUE (tahun, semester)
);

-- ── 7. Tabel penilaian ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS penilaian (
    id_penilaian   SERIAL        PRIMARY KEY,
    id_vendor      INTEGER       NOT NULL REFERENCES vendor(id_vendor)     ON DELETE CASCADE,
    id_kriteria    INTEGER       NOT NULL REFERENCES kriteria(id_kriteria)  ON DELETE CASCADE,
    id_periode     INTEGER       NOT NULL REFERENCES periode(id_periode)    ON DELETE CASCADE,
    id_jenis       INTEGER       NOT NULL REFERENCES jenis_barang(id_jenis) ON DELETE CASCADE,
    id_user        INTEGER       NOT NULL REFERENCES users(id_user)         ON DELETE SET NULL,
    nilai_kriteria NUMERIC(7,2)  NOT NULL DEFAULT 0,
    total_nilai    NUMERIC(7,2)  NOT NULL DEFAULT 0,
    grade          VARCHAR(2)    NOT NULL DEFAULT 'E',
    updated_at     TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    UNIQUE (id_vendor, id_kriteria, id_periode, id_jenis)
);

-- ── 8. Tabel hasil_saw ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS hasil_saw (
    id_hasil          SERIAL        PRIMARY KEY,
    id_vendor         INTEGER       NOT NULL REFERENCES vendor(id_vendor)     ON DELETE CASCADE,
    id_jenis          INTEGER       NOT NULL REFERENCES jenis_barang(id_jenis) ON DELETE CASCADE,
    id_periode        INTEGER       NOT NULL REFERENCES periode(id_periode)    ON DELETE CASCADE,
    nilai_preferensi  NUMERIC(12,6) NOT NULL DEFAULT 0,
    peringkat         INTEGER       NOT NULL DEFAULT 0,
    created_at        TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- ── 9. Tabel detail_normalisasi ──────────────────────────────
CREATE TABLE IF NOT EXISTS detail_normalisasi (
    id_detail          SERIAL        PRIMARY KEY,
    id_hasil           INTEGER       NOT NULL REFERENCES hasil_saw(id_hasil) ON DELETE CASCADE,
    id_kriteria        INTEGER       NOT NULL REFERENCES kriteria(id_kriteria),
    nilai_asli         NUMERIC(7,2)  NOT NULL DEFAULT 0,
    nilai_normalisasi  NUMERIC(12,6) NOT NULL DEFAULT 0,
    bobot_kriteria     NUMERIC(5,4)  NOT NULL DEFAULT 0,
    nilai_terbobot     NUMERIC(12,6) NOT NULL DEFAULT 0
);

-- ── Indexes ──────────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_penilaian_periode_jenis ON penilaian(id_periode, id_jenis);
CREATE INDEX IF NOT EXISTS idx_hasil_saw_periode_jenis ON hasil_saw(id_periode, id_jenis);
CREATE INDEX IF NOT EXISTS idx_detail_id_hasil          ON detail_normalisasi(id_hasil);
CREATE INDEX IF NOT EXISTS idx_sessions_last_activity   ON sessions(last_activity);

-- ── Data Awal: 1 user Administrator ──────────────────────────
-- Password default: admin123 (bcrypt hash)
INSERT INTO users (username, password, nama_lengkap, role, status_aktif)
VALUES (
    'admin',
    '$2y$12$6t8wNSbP9P3R7Vy1LGzg0.PaF4hZQk3v5fXJxoANeK1UBfGhIMrvy',
    'Administrator',
    'Administrator',
    TRUE
)
ON CONFLICT (username) DO NOTHING;

-- ── Konfirmasi Selesai ────────────────────────────────────────
SELECT 'Schema berhasil dibuat! Login dengan username: admin, password: admin123' AS status;
