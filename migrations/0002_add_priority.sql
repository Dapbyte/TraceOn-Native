ALTER TABLE todos ADD COLUMN priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium' AFTER status;
