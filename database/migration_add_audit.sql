-- Run this in phpMyAdmin (select nothing first, just open the SQL tab and paste/import).
-- Adds the history log + access control tables WITHOUT deleting your existing data.
USE barangay_incident_db;

CREATE TABLE IF NOT EXISTS PERMISSION(Permission_ID INT AUTO_INCREMENT PRIMARY KEY,Permission_Code VARCHAR(30) NOT NULL UNIQUE,
 Permission_Name VARCHAR(60) NOT NULL,Description VARCHAR(255) NOT NULL);
CREATE TABLE IF NOT EXISTS USER_PERMISSION(User_ID INT NOT NULL,Permission_ID INT NOT NULL,Granted_By INT NULL,Granted_At DATETIME DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(User_ID,Permission_ID),
 FOREIGN KEY(User_ID) REFERENCES USERS(User_ID) ON DELETE CASCADE,
 FOREIGN KEY(Permission_ID) REFERENCES PERMISSION(Permission_ID),
 FOREIGN KEY(Granted_By) REFERENCES USERS(User_ID));
CREATE TABLE IF NOT EXISTS ACTIVITY_LOG(Log_ID INT AUTO_INCREMENT PRIMARY KEY,User_ID INT NULL,Action VARCHAR(40) NOT NULL,Incident_Ref VARCHAR(10) NULL,
 Details VARCHAR(255) NULL,IP_Address VARCHAR(45) NULL,Logged_At DATETIME DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(User_ID) REFERENCES USERS(User_ID),FOREIGN KEY(Incident_Ref) REFERENCES INCIDENT(Incident_Ref));

INSERT IGNORE INTO PERMISSION(Permission_Code,Permission_Name,Description) VALUES
('VIEW_ASSIGNED','View assigned cases','See the incidents assigned to you, including complainant details.'),
('UPDATE_STATUS','Update case status','Change the status (Open, Under Investigation, Referred, Closed) of your assigned incidents.'),
('ADD_FOLLOWUP','Add follow-up notes','Record dated follow-up notes (site visits, calls, mediation) on your assigned incidents.'),
('VIEW_ALL','View all incidents (read-only)','Monitor every incident in the barangay without being able to change it. Meant for public officials who oversee cases.');

-- Give existing Officer accounts the default access so their dashboards keep working
INSERT IGNORE INTO USER_PERMISSION(User_ID,Permission_ID,Granted_By)
SELECT u.User_ID, p.Permission_ID, (SELECT User_ID FROM USERS WHERE Username='admin')
FROM USERS u JOIN PERMISSION p ON p.Permission_Code IN ('VIEW_ASSIGNED','UPDATE_STATUS','ADD_FOLLOWUP')
WHERE u.Role='Officer';
