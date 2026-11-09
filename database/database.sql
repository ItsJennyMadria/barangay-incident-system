DROP DATABASE IF EXISTS barangay_incident_db;
CREATE DATABASE barangay_incident_db CHARACTER SET utf8mb4;
USE barangay_incident_db;
CREATE TABLE SERVICE(Service_ID INT AUTO_INCREMENT PRIMARY KEY,Service_Name VARCHAR(60) NOT NULL UNIQUE,Description VARCHAR(255) NOT NULL);
CREATE TABLE INCIDENT_TYPE(Type_ID INT AUTO_INCREMENT PRIMARY KEY,Type_Name VARCHAR(50) NOT NULL UNIQUE,Service_ID INT NOT NULL,
 Description VARCHAR(255) NOT NULL,Is_Urgent TINYINT(1) NOT NULL DEFAULT 0,FOREIGN KEY(Service_ID) REFERENCES SERVICE(Service_ID));
CREATE TABLE INCIDENT_STATUS(Status_ID INT AUTO_INCREMENT PRIMARY KEY,Status_Name VARCHAR(50) NOT NULL UNIQUE);
CREATE TABLE RESIDENT(Resident_ID VARCHAR(10) PRIMARY KEY,Resident_Name VARCHAR(100) NOT NULL,Contact VARCHAR(20) NOT NULL);
CREATE TABLE OFFICER(Officer_ID VARCHAR(10) PRIMARY KEY,Officer_Name VARCHAR(100) NOT NULL);
CREATE TABLE OFFICER_SERVICE(Officer_ID VARCHAR(10) NOT NULL,Service_ID INT NOT NULL,PRIMARY KEY(Officer_ID,Service_ID),
 FOREIGN KEY(Officer_ID) REFERENCES OFFICER(Officer_ID),FOREIGN KEY(Service_ID) REFERENCES SERVICE(Service_ID));
CREATE TABLE INCIDENT(
 Incident_Ref VARCHAR(10) PRIMARY KEY,Incident_Date DATE NOT NULL,Incident_Time TIME NULL,Location VARCHAR(100) NOT NULL,
 Description VARCHAR(500) NULL,Other_Specify VARCHAR(100) NULL,Reported_At DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
 Type_ID INT NOT NULL,Resident_ID VARCHAR(10) NOT NULL,Officer_ID VARCHAR(10) NULL,Status_ID INT NOT NULL,
 FOREIGN KEY(Type_ID) REFERENCES INCIDENT_TYPE(Type_ID),FOREIGN KEY(Resident_ID) REFERENCES RESIDENT(Resident_ID),
 FOREIGN KEY(Officer_ID) REFERENCES OFFICER(Officer_ID),FOREIGN KEY(Status_ID) REFERENCES INCIDENT_STATUS(Status_ID));
CREATE TABLE INCIDENT_FOLLOWUP(Followup_ID INT AUTO_INCREMENT PRIMARY KEY,Incident_Ref VARCHAR(10) NOT NULL,
 Follow_up_Date DATE NOT NULL,Follow_up_Note VARCHAR(255) NOT NULL,
 FOREIGN KEY(Incident_Ref) REFERENCES INCIDENT(Incident_Ref) ON DELETE CASCADE);
CREATE TABLE USERS(User_ID INT AUTO_INCREMENT PRIMARY KEY,Username VARCHAR(30) NOT NULL UNIQUE,
 Password_Hash VARCHAR(255) NOT NULL,Role ENUM('Admin','Officer') NOT NULL,Officer_ID VARCHAR(10) NOT NULL UNIQUE,
 FOREIGN KEY(Officer_ID) REFERENCES OFFICER(Officer_ID));
CREATE TABLE PERMISSION(Permission_ID INT AUTO_INCREMENT PRIMARY KEY,Permission_Code VARCHAR(30) NOT NULL UNIQUE,
 Permission_Name VARCHAR(60) NOT NULL,Description VARCHAR(255) NOT NULL);
CREATE TABLE USER_PERMISSION(User_ID INT NOT NULL,Permission_ID INT NOT NULL,Granted_By INT NULL,Granted_At DATETIME DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(User_ID,Permission_ID),FOREIGN KEY(User_ID) REFERENCES USERS(User_ID) ON DELETE CASCADE,
 FOREIGN KEY(Permission_ID) REFERENCES PERMISSION(Permission_ID),FOREIGN KEY(Granted_By) REFERENCES USERS(User_ID));
CREATE TABLE ACTIVITY_LOG(Log_ID INT AUTO_INCREMENT PRIMARY KEY,User_ID INT NULL,Action VARCHAR(40) NOT NULL,Incident_Ref VARCHAR(10) NULL,
 Details VARCHAR(255) NULL,IP_Address VARCHAR(45) NULL,Logged_At DATETIME DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(User_ID) REFERENCES USERS(User_ID),FOREIGN KEY(Incident_Ref) REFERENCES INCIDENT(Incident_Ref));

INSERT INTO SERVICE(Service_Name,Description) VALUES
('Peace and Order','Noise, disputes, threats, theft and other peace-keeping concerns.'),
('Fire and Rescue','Fires, rescue work and life-threatening emergencies.'),
('Health and Sanitation','Medical emergencies, garbage, sanitation and disease concerns.'),
('Disaster Response','Floods, landslides, fallen trees and storm damage.'),
('Traffic and Road Safety','Road accidents, obstructions and traffic concerns.'),
('General Barangay Services','Property damage, animals, and anything that does not fit elsewhere.');
INSERT INTO INCIDENT_TYPE(Type_Name,Service_ID,Description,Is_Urgent) VALUES
('Noise',1,'Loud music, karaoke, construction or other disturbing noise.',0),
('Property damage',6,'Vandalism or damage to fences, walls, vehicles or belongings.',0),
('Traffic',5,'Road obstruction, illegal parking or reckless driving.',0),
('Dispute',1,'Quarrels or neighbor and boundary disputes that need mediation.',0),
('Other',6,'Something not listed. You will be asked to describe it so we can route it correctly.',0),
('Theft / Robbery',1,'Stolen or snatched belongings, break-ins.',1),
('Threat / Physical harm',1,'Threats, fighting, assault or domestic violence.',1),
('Fire',2,'House, grass or vehicle fire, or heavy smoke.',1),
('Medical emergency',3,'A person is injured, very sick or unconscious.',1),
('Road accident',5,'A collision or crash, with or without injuries.',1),
('Flood / landslide / fallen tree',4,'Natural hazards blocking roads or endangering homes.',1),
('Garbage / sanitation',3,'Uncollected garbage, clogged canals, foul odor or stagnant water.',0),
('Stray / dangerous animals',6,'Aggressive dogs, loose livestock or animal bites.',0);
INSERT INTO INCIDENT_STATUS(Status_Name) VALUES('Open'),('Under Investigation'),('Referred'),('Closed');
INSERT INTO RESIDENT VALUES('R-011','Ana Cruz','09170000011'),('R-012','Nilo Reyes','09170000012'),('R-013','Eva Lim','09170000013'),('R-014','Ben Go','09170000014');
INSERT INTO OFFICER VALUES('O-00','System Administrator'),('O-01','Leo Ramos'),('O-02','Mia Tan');
INSERT INTO OFFICER_SERVICE VALUES('O-01',1),('O-01',5),('O-02',1),('O-02',6);
INSERT INTO INCIDENT(Incident_Ref,Incident_Date,Incident_Time,Location,Description,Type_ID,Resident_ID,Officer_ID,Status_ID) VALUES
('INC-001','2026-09-03','21:30:00','Purok 1','Loud karaoke past 10 PM.',1,'R-011','O-01',1),
('INC-002','2026-09-05','14:00:00','Purok 3','Neighbor fence was damaged.',2,'R-012','O-02',2),
('INC-003','2026-09-08','07:45:00','Purok 1','Tricycles blocking the road.',3,'R-011','O-01',3),
('INC-004','2026-09-10','18:00:00','Purok 5','Boundary quarrel between neighbors.',4,'R-013','O-02',4),
('INC-005','2026-09-11','23:15:00','Purok 2','Noise from a drinking session.',1,'R-014','O-01',1),
('INC-006','2026-09-12','16:20:00','Purok 3','Argument over a shared well.',4,'R-012','O-02',2);
INSERT INTO INCIDENT_FOLLOWUP(Incident_Ref,Follow_up_Date,Follow_up_Note) VALUES
('INC-001','2026-09-04','Visited site'),('INC-001','2026-09-06','Called parties'),('INC-002','2026-09-06','Photos collected'),
('INC-003','2026-09-09','Interview'),('INC-003','2026-09-10','Endorsed to traffic unit'),('INC-004','2026-09-11','Mediation completed'),
('INC-006','2026-09-13','Initial visit');
-- Default admin: username admin / password Admin@123 (change after first login)
INSERT INTO USERS(Username,Password_Hash,Role,Officer_ID) VALUES('admin','$2y$12$twtHwqmDDVwO0/KHc.Ah8uflYwJVimwFwbkMURiTS4GD.i2X24C3.','Admin','O-00');
INSERT INTO PERMISSION(Permission_Code,Permission_Name,Description) VALUES
('VIEW_ASSIGNED','View assigned cases','See the incidents assigned to you, including complainant details.'),
('UPDATE_STATUS','Update case status','Change the status (Open, Under Investigation, Referred, Closed) of your assigned incidents.'),
('ADD_FOLLOWUP','Add follow-up notes','Record dated follow-up notes (site visits, calls, mediation) on your assigned incidents.'),
('VIEW_ALL','View all incidents (read-only)','Monitor every incident in the barangay without being able to change it. Meant for public officials who oversee cases.');
