-- Upgrade an EXISTING database (keeps your data). Run ONCE in phpMyAdmin > barangay_incident_db > Import/SQL.
USE barangay_incident_db;
CREATE TABLE SERVICE(Service_ID INT AUTO_INCREMENT PRIMARY KEY,Service_Name VARCHAR(60) NOT NULL UNIQUE,Description VARCHAR(255) NOT NULL);
INSERT INTO SERVICE(Service_Name,Description) VALUES
('Peace and Order','Noise, disputes, threats, theft and other peace-keeping concerns.'),
('Fire and Rescue','Fires, rescue work and life-threatening emergencies.'),
('Health and Sanitation','Medical emergencies, garbage, sanitation and disease concerns.'),
('Disaster Response','Floods, landslides, fallen trees and storm damage.'),
('Traffic and Road Safety','Road accidents, obstructions and traffic concerns.'),
('General Barangay Services','Property damage, animals, and anything that does not fit elsewhere.');
ALTER TABLE INCIDENT_TYPE ADD COLUMN Service_ID INT NOT NULL DEFAULT 6,ADD COLUMN Description VARCHAR(255) NOT NULL DEFAULT '',
 ADD COLUMN Is_Urgent TINYINT(1) NOT NULL DEFAULT 0,ADD FOREIGN KEY(Service_ID) REFERENCES SERVICE(Service_ID);
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
('Stray / dangerous animals',6,'Aggressive dogs, loose livestock or animal bites.',0)
ON DUPLICATE KEY UPDATE Service_ID=VALUES(Service_ID),Description=VALUES(Description),Is_Urgent=VALUES(Is_Urgent);
ALTER TABLE INCIDENT ADD COLUMN Incident_Time TIME NULL,ADD COLUMN Description VARCHAR(500) NULL,ADD COLUMN Other_Specify VARCHAR(100) NULL,
 ADD COLUMN Reported_At DATETIME NULL DEFAULT CURRENT_TIMESTAMP;
CREATE TABLE OFFICER_SERVICE(Officer_ID VARCHAR(10) NOT NULL,Service_ID INT NOT NULL,PRIMARY KEY(Officer_ID,Service_ID),
 FOREIGN KEY(Officer_ID) REFERENCES OFFICER(Officer_ID),FOREIGN KEY(Service_ID) REFERENCES SERVICE(Service_ID));
INSERT IGNORE INTO OFFICER_SERVICE VALUES('O-01',1),('O-01',5),('O-02',1),('O-02',6);
