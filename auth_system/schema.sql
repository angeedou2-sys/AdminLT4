CREATE DATABASE IF NOT EXISTS auth_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE auth_db;

CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    password        VARCHAR(255)    NOT NULL,
    nationalite     VARCHAR(100)    NOT NULL,
    sexe            ENUM('M','F','Autre') NOT NULL,
    diplome         VARCHAR(100)    NOT NULL,
    photo           LONGBLOB,
    photo_mime      VARCHAR(50),
    verif_code      VARCHAR(6),
    verif_expires   DATETIME,
    is_verified     TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
);
