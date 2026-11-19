-- Patient Portal System - database schema
-- Import this file in phpMyAdmin (or: mysql -u root < schema.sql)

CREATE DATABASE IF NOT EXISTS patient_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE patient_portal;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS contact_messages, audit_log, login_attempts, medication_reminders, notifications, payments, bills,
    messages, prescription_requests, prescriptions, lab_results, appointments, medical_records,
    doctors_profile, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role            ENUM('patient','doctor','admin') NOT NULL DEFAULT 'patient',
    full_name       VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    phone           VARCHAR(20)  NULL,
    dob             DATE         NULL,
    gender          ENUM('male','female','other') NULL,
    address         VARCHAR(255) NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    failed_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until    DATETIME     NULL,
    last_login      DATETIME     NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE doctors_profile (
    user_id        INT UNSIGNED PRIMARY KEY,
    specialization VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE medical_records (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id  INT UNSIGNED NOT NULL,
    doctor_id   INT UNSIGNED NULL,
    diagnosis   VARCHAR(255) NOT NULL,
    notes       TEXT NULL,
    record_date DATE NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)  REFERENCES users(id) ON DELETE SET NULL,
    INDEX (patient_id)
) ENGINE=InnoDB;

CREATE TABLE appointments (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id       INT UNSIGNED NOT NULL,
    doctor_id        INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason           VARCHAR(255) NOT NULL,
    status           ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)  REFERENCES users(id) ON DELETE CASCADE,
    INDEX (patient_id), INDEX (doctor_id, appointment_date)
) ENGINE=InnoDB;

CREATE TABLE lab_results (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id   INT UNSIGNED NOT NULL,
    doctor_id    INT UNSIGNED NULL,
    test_name    VARCHAR(150) NOT NULL,
    result       VARCHAR(255) NOT NULL,
    normal_range VARCHAR(100) NULL,
    status       ENUM('normal','abnormal','critical','pending') NOT NULL DEFAULT 'normal',
    remarks      TEXT NULL,
    test_date    DATE NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)  REFERENCES users(id) ON DELETE SET NULL,
    INDEX (patient_id)
) ENGINE=InnoDB;

CREATE TABLE prescriptions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id  INT UNSIGNED NOT NULL,
    doctor_id   INT UNSIGNED NULL,
    medication  VARCHAR(150) NOT NULL,
    dosage      VARCHAR(100) NOT NULL,
    frequency   VARCHAR(100) NOT NULL,
    start_date  DATE NOT NULL,
    end_date    DATE NULL,
    renewable   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)  REFERENCES users(id) ON DELETE SET NULL,
    INDEX (patient_id)
) ENGINE=InnoDB;

CREATE TABLE prescription_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT UNSIGNED NOT NULL,
    patient_id      INT UNSIGNED NOT NULL,
    patient_note    VARCHAR(255) NULL,
    status          ENUM('pending','approved','denied') NOT NULL DEFAULT 'pending',
    doctor_note     VARCHAR(255) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id)      REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    subject     VARCHAR(150) NOT NULL,
    body        TEXT NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    sent_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (receiver_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE bills (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id  INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    due_date    DATE NULL,
    status      ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (patient_id)
) ENGINE=InnoDB;

CREATE TABLE payments (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bill_id INT UNSIGNED NOT NULL,
    amount  DECIMAL(10,2) NOT NULL,
    method  ENUM('cash','card','mobile_money','insurance','bank') NOT NULL,
    paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    type       VARCHAR(30) NOT NULL,
    message    VARCHAR(255) NOT NULL,
    link       VARCHAR(255) NULL,
    ref_key    VARCHAR(100) NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_ref (user_id, ref_key),
    INDEX (user_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE medication_reminders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT UNSIGNED NOT NULL,
    prescription_id INT UNSIGNED NOT NULL,
    remind_time     TIME NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id)      REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip           VARCHAR(45) NOT NULL,
    email        VARCHAR(150) NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (ip, attempted_at)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NULL,
    action     VARCHAR(255) NOT NULL,
    ip         VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (created_at)
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    phone      VARCHAR(20)  NULL,
    subject    VARCHAR(50)  NOT NULL,
    message    TEXT NOT NULL,
    user_id    INT UNSIGNED NULL,
    ip         VARCHAR(45)  NULL,
    status     ENUM('new','read','replied') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (status), INDEX (ip, created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Seed data. CHANGE THESE PASSWORDS after first login.
--   admin@portal.local   / Admin@123
--   doctor@portal.local  / Doctor@123
--   patient@portal.local / Patient@123
-- ---------------------------------------------------------------
INSERT INTO users (id, role, full_name, email, password_hash, phone, dob, gender) VALUES
(1, 'admin',   'System Administrator', 'admin@portal.local',   '$2y$10$Aeo2Hcb1qtABUQ88GN5INux/X4Wydf/D2WpQySnjIACLZBIPKmKQK', '0700000001', NULL, NULL),
(2, 'doctor',  'Dr. Grace Mwakyusa',   'doctor@portal.local',  '$2y$10$U/KtKZCJAaUk6h/kwS7Wzu1tM/Nozke5R3QaEEsM3albAWQcJ9PXG', '0700000002', '1985-04-12', 'female'),
(3, 'patient', 'John Patient',         'patient@portal.local', '$2y$10$8civSD6n83TbNwSu7u6bEeDVB87eYxRMitqudANbBEb6MJc/AAIvG', '0700000003', '1995-08-20', 'male');

INSERT INTO doctors_profile (user_id, specialization) VALUES (2, 'General Medicine');

INSERT INTO medical_records (patient_id, doctor_id, diagnosis, notes, record_date) VALUES
(3, 2, 'Malaria (uncomplicated)', 'Treated with ACT. Full recovery expected.', DATE_SUB(CURDATE(), INTERVAL 90 DAY)),
(3, 2, 'Hypertension - Stage 1', 'Lifestyle changes advised. Monitor blood pressure monthly.', DATE_SUB(CURDATE(), INTERVAL 20 DAY));

INSERT INTO lab_results (patient_id, doctor_id, test_name, result, normal_range, status, remarks, test_date) VALUES
(3, 2, 'Full Blood Count - Haemoglobin', '13.8 g/dL', '13.5 - 17.5 g/dL', 'normal', NULL, DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
(3, 2, 'Fasting Blood Glucose', '6.4 mmol/L', '3.9 - 5.6 mmol/L', 'abnormal', 'Slightly elevated. Repeat in 3 months.', DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
(3, 2, 'Malaria Rapid Test', 'Negative', 'Negative', 'normal', NULL, DATE_SUB(CURDATE(), INTERVAL 5 DAY));

INSERT INTO prescriptions (patient_id, doctor_id, medication, dosage, frequency, start_date, end_date, renewable) VALUES
(3, 2, 'Amlodipine', '5 mg', 'Once daily', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 1),
(3, 2, 'Paracetamol', '500 mg', 'Every 8 hours as needed', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 0);

INSERT INTO medication_reminders (patient_id, prescription_id, remind_time) VALUES (3, 1, '08:00:00');

INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES
(3, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'Blood pressure follow-up', 'confirmed');

INSERT INTO bills (patient_id, description, amount, due_date, status) VALUES
(3, 'Consultation fee', 30000.00, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'unpaid'),
(3, 'Laboratory tests', 45000.00, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'paid');

INSERT INTO payments (bill_id, amount, method) VALUES (2, 45000.00, 'mobile_money');

INSERT INTO messages (sender_id, receiver_id, subject, body) VALUES
(2, 3, 'Your recent lab results', 'Hello John, your glucose level is slightly high. Please reduce sugar intake and we will re-test in 3 months.');
