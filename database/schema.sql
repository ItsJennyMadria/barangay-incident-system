CREATE DATABASE IF NOT EXISTS barangay_db;
USE barangay_db;

-- 1. RESIDENT Table
CREATE TABLE RESIDENT (
    Resident_ID VARCHAR(20) PRIMARY KEY,
    Resident_Name VARCHAR(100) NOT NULL,
    Contact VARCHAR(20) NOT NULL
);

-- 2. OFFICER Table
CREATE TABLE OFFICER (
    Officer_ID VARCHAR(20) PRIMARY KEY,
    Officer_Name VARCHAR(100) NOT NULL
);

-- 3. INCIDENT_TYPE Table
CREATE TABLE INCIDENT_TYPE (
    Type_ID INT AUTO_INCREMENT PRIMARY KEY,
    Type_Name VARCHAR(50) NOT NULL UNIQUE
);

-- 4. INCIDENT_STATUS Table
CREATE TABLE INCIDENT_STATUS (
    Status_ID INT AUTO_INCREMENT PRIMARY KEY,
    Status_Name VARCHAR(50) NOT NULL UNIQUE
);

-- 5. USERS Table (RBAC + Mandatory 1-to-1 Officer Link)
CREATE TABLE USERS (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password_Hash VARCHAR(255) NOT NULL,
    Role ENUM('Admin', 'Officer') NOT NULL,
    Officer_ID VARCHAR(20) NOT NULL UNIQUE,
    FOREIGN KEY (Officer_ID) REFERENCES OFFICER(Officer_ID) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- 6. INCIDENT Table
CREATE TABLE INCIDENT (
    Incident_Ref VARCHAR(20) PRIMARY KEY,
    Incident_Date DATE NOT NULL,
    Location VARCHAR(150) NOT NULL,
    Type_ID INT NOT NULL,
    Resident_ID VARCHAR(20) NOT NULL,
    Officer_ID VARCHAR(20) NOT NULL,
    Status_ID INT NOT NULL,
    FOREIGN KEY (Type_ID) REFERENCES INCIDENT_TYPE(Type_ID),
    FOREIGN KEY (Resident_ID) REFERENCES RESIDENT(Resident_ID),
    FOREIGN KEY (Officer_ID) REFERENCES OFFICER(Officer_ID),
    FOREIGN KEY (Status_ID) REFERENCES INCIDENT_STATUS(Status_ID)
);

-- 7. INCIDENT_FOLLOWUP Table
CREATE TABLE INCIDENT_FOLLOWUP (
    Followup_ID INT AUTO_INCREMENT PRIMARY KEY,
    Incident_Ref VARCHAR(20) NOT NULL,
    Follow_up_Date DATE NOT NULL,
    Follow_up_Note TEXT NOT NULL,
    FOREIGN KEY (Incident_Ref) REFERENCES INCIDENT(Incident_Ref) ON DELETE CASCADE
);

-- Seed Initial Officers & Lookups
INSERT INTO OFFICER (Officer_ID, Officer_Name) VALUES 
('O-01', 'Leo Ramos'),
('O-02', 'Mia Tan');

INSERT INTO RESIDENT (Resident_ID, Resident_Name, Contact) VALUES
('R-011', 'Ana Cruz', '09170000011'),
('R-012', 'Nilo Reyes', '09170000012');

INSERT INTO INCIDENT_TYPE (Type_Name) VALUES 
('Noise'), ('Property damage'), ('Traffic'), ('Dispute');

INSERT INTO INCIDENT_STATUS (Status_Name) VALUES 
('Open'), ('Under Investigation'), ('Referred'), ('Closed');

INSERT INTO INCIDENT (Incident_Ref, Incident_Date, Location, Type_ID, Resident_ID, Officer_ID, Status_ID) VALUES
('INC-001', '2026-09-03', 'Purok 1', 1, 'R-011', 'O-01', 1),
('INC-002', '2026-09-05', 'Purok 3', 2, 'R-012', 'O-02', 2);