                    /*
                    SGA SECTION
                    */

--Crear la tabla para insertar los datos
CREATE TABLE job_SGA_Table(USED_MB number,FREE_MB number,TOTAL_MB number,TIME date);

/*
Vaciar la tabla
TRUNCATE TABLE job_SGA_Table;

DROP TABLE job_SGA_Table;
*/

--CReacion de la tabla que contiene los valores para graficar
select  USED_MB,TIME, TOTAL_MB from job_SGA_Table;

--Procedimiento SGA
CREATE OR REPLACE PROCEDURE prc_ins_job_SGA AS
  BEGIN
	INSERT INTO job_SGA_Table(USED_MB,FREE_MB,TOTAL_MB,TIME)
	select round(used.bytes /1024/1024 ,2) used_mb, round(free.bytes /1024/1024 ,2) free_mb,
  (round(tot.bytes /1024/1024 ,2)) TOTAL_mb, current_date Time
	from (select sum(bytes) bytes
	from v$sgastat
	where name != 'free memory') used,
  (select sum(bytes) bytes from v$sgastat where name = 'free memory') free,
  (select sum(bytes) bytes FROM v$sgastat)  tot ;
	COMMIT;
END prc_ins_job_SGA;
/


--cantidad de registros en la TABLESPACE
--se usara posteriormete para limitar la cantidad de valores en la tabla
 SELECT count(*)
  FROM job_SGA_Table;


  
--Automatizar el procedimiento
BEGIN
 DBMS_SCHEDULER.CREATE_JOB (
   job_name        => 'Insert_data_SGA',
   job_type        => 'STORED_PROCEDURE',
   job_action      => 'prc_ins_job_SGA',
   repeat_interval => 'freq=secondly;interval=5',
   enabled         => TRUE);
END;
/

--Se comienza a hacer el procedimiento del SGA
EXEC DBMS_SCHEDULER.ENABLE('Insert_data_SGA');

/*eliminar SCHEDULER
BEGIN  dbms_scheduler.drop_job(job_name => 'Insert_data_SGA');
END;
*/

SELECT TOTAL_MB FROM job_SGA_Table FETCH FIRST 1 ROWS ONLY;


CREATE OR REPLACE FUNCTION fun_get_sga_maxsize RETURN NUMBER IS
tot number;
BEGIN
    SELECT TOTAL_MB INTO tot from job_SGA_Table FETCH FIRST 1 ROWS ONLY;
    RETURN tot;
    EXCEPTION 
        WHEN no_data_found THEN RETURN (0);
END fun_get_sga_maxsize;
/
show error

--Prueba de la funcion
select fun_get_sga_maxsize TOTAL FROM dual;

CREATE OR REPLACE FUNCTION fun_get_sga_usedsize RETURN NUMBER IS
val number;
BEGIN
	select round(used.bytes /1024/1024 ,2) used_mb
    into val
	from (select sum(bytes) bytes
	from v$sgastat
	where name != 'free memory') used;
    return val;
END fun_get_sga_usedsize;
/
SHOW ERROR

--prueba de la funcion
SELECT fun_get_sga_usedsize FROM dual;


alter SESSION set NLS_TIMESTAMP_FORMAT = 'yyyy-mm-dd/hh24:mi:ss';
alter SESSION set NLS_DATE_FORMAT = 'yyyy-mm-dd/hh24:mi:ss';
select * from nls_database_parameters;

CREATE OR REPLACE FUNCTION fun_get_isDiffLess5s(Ptime IN TIMESTAMP) RETURN INT IS
BEGIN
    IF(Ptime=null) THEN
        RETURN -1;
    ELSIF (Ptime >= (systimestamp  - interval '5' minute)) THEN
        RETURN 1;
    ELSE
        RETURN 0;
    END IF;
END fun_get_isDiffLess5s;
/
SHOW ERROR

--prueba de la funcion
SELECT fun_get_isDiffLess5s('2022-11-14/14:14:23') as VAL from dual;

CREATE OR REPLACE FUNCTION fun_get_sgaAlerts RETURN SYS_REFCURSOR IS
  cr SYS_REFCURSOR;
BEGIN
    OPEN cr FOR
        SELECT au.USERNAME username,
            to_timestamp(vs.first_load_time,'yyyy-mm-dd/hh24:mi:ss') load_time,
            vs.sql_text SQL,
            vs.OBJECT_STATUS status
        FROM v$sqlarea vs , all_users au
        where (au.user_id(+)=vs.parsing_user_id) and (executions >= 1)  AND fun_get_isDiffLess5s(first_load_time)=1
        order by load_time asc;
    RETURN cr;
    EXCEPTION 
        WHEN no_data_found THEN RETURN NULL;
END fun_get_sgaAlerts;
/
show error

                    /*
                    TABLESPACE SECTION
                    */
                    
                    
SELECT
      ts.tablespace_name,
      TRUNC("SIZE(B)", 2)                                  "BYTES_SIZE",
      TRUNC(fr."FREE(B)", 2)                               "BYTES_FREE",
      TRUNC("SIZE(B)" - "FREE(B)", 2)                      "BYTES_USED"
FROM
      (SELECT tablespace_name, SUM(bytes) "FREE(B)" FROM dba_free_space GROUP BY tablespace_name) fr,
      (SELECT tablespace_name, SUM(bytes) "SIZE(B)", SUM(maxbytes) "MAX_EXT" FROM dba_data_files GROUP BY tablespace_name) df,
      (SELECT tablespace_name FROM dba_tablespaces) ts
WHERE fr.tablespace_name = df.tablespace_name AND fr.tablespace_name = ts.tablespace_name;



CREATE OR REPLACE FUNCTION fun_get_TS_allinfo RETURN SYS_REFCURSOR IS
  cr SYS_REFCURSOR;
BEGIN
    OPEN cr FOR
        WITH ts_sizes AS (
            SELECT
                df.tablespace_name,
                SUM(df.bytes) as used_bytes,
                SUM(df.maxbytes) as total_bytes, -- maxbytes can be 0 if autoextend is off for a file but generally refers to potential size
                SUM(CASE WHEN df.autoextensible = 'YES' THEN df.maxbytes ELSE df.bytes END) as effective_total_bytes, -- A more realistic total considering autoextend
                MIN(d.creation_time) as creation_time -- Earliest creation time for any file in the tablespace
            FROM
                dba_data_files df
            JOIN
                v$datafile d ON df.file_id = d.file# -- Joining by file_id/file#
            GROUP BY
                df.tablespace_name
        ),
        ts_free_space AS (
            SELECT
                tablespace_name,
                SUM(bytes) as free_bytes
            FROM
                dba_free_space
            GROUP BY
                tablespace_name
        )
        SELECT
            s.tablespace_name "NAME",
            ROUND(s.used_bytes / (1024*1024), 2) "USED_MB",
            ROUND((s.used_bytes - NVL(f.free_bytes, 0)) / (1024*1024), 2) "ACTUAL_USED_MB", -- Used space minus free fragments = actual data
            ROUND(NVL(f.free_bytes, 0) / (1024*1024), 2) "FREE_MB",
            ROUND(s.effective_total_bytes / (1024*1024), 2) "TOTAL_MB", -- Using effective_total_bytes
            GREATEST(1, ROUND(SYSDATE - s.creation_time, 0)) "DAYS_CREATED", -- Ensure days_created is at least 1 to avoid division by zero later
            ROUND( ( (s.used_bytes - NVL(f.free_bytes, 0)) / (1024*1024) ) / GREATEST(1, ROUND(SYSDATE - s.creation_time, 0)), 2) "DAILY_GROWTH_MB", -- Growth based on actual used space
            CASE
                WHEN ROUND( ( (s.used_bytes - NVL(f.free_bytes, 0)) / (1024*1024) ) / GREATEST(1, ROUND(SYSDATE - s.creation_time, 0)), 2) <= 0 THEN 9999 -- If no growth or negative growth, remaining time is effectively infinite (or very large)
                ELSE ROUND( (NVL(f.free_bytes, 0) / (1024*1024)) / (ROUND( ( (s.used_bytes - NVL(f.free_bytes, 0)) / (1024*1024) ) / GREATEST(1, ROUND(SYSDATE - s.creation_time, 0)), 2)), 0)
            END "REMAINING_TIME_DAYS"
        FROM
            ts_sizes s
        LEFT JOIN
            ts_free_space f ON s.tablespace_name = f.tablespace_name
        ORDER BY
            s.tablespace_name;
    RETURN cr;
    EXCEPTION
        WHEN OTHERS THEN
            -- Consider logging the error: DBMS_OUTPUT.PUT_LINE(SQLERRM);
            RETURN NULL; -- Or raise the exception
END fun_get_TS_allinfo;
/
SHOW ERROR




SELECT
   ts.tablespace_name, "File Count",
   TRUNC("SIZE(MB)", 2) "Size(MB)",
   TRUNC(fr."FREE(MB)", 2) "Free(MB)",
   TRUNC("SIZE(MB)" - "FREE(MB)", 2) "Used(MB)",
   df."MAX_EXT" "Max Ext(MB)",
   (fr."FREE(MB)" / df."SIZE(MB)") * 100 "% Free"
FROM
   (SELECT tablespace_name, SUM (bytes) / (1024 * 1024) "FREE(MB)"
   FROM dba_free_space
    GROUP BY tablespace_name) fr,
(SELECT tablespace_name, SUM(bytes) / (1024 * 1024) "SIZE(MB)", COUNT(*)
"File Count", SUM(maxbytes) / (1024 * 1024) "MAX_EXT"
FROM dba_data_files
GROUP BY tablespace_name) df,
(SELECT tablespace_name
FROM dba_tablespaces) ts
WHERE fr.tablespace_name = df.tablespace_name (+)
AND fr.tablespace_name = ts.tablespace_name (+)
ORDER BY "% Free" desc;


select t.name tablespace_name,
MIN(d.creation_time) CREATE_TIME,
ROUND(SYSDATE - MIN(d.creation_time),0) DAYS_CREATED
from v$datafile d, v$tablespace t
where d.ts# = t.ts# 
group by t.name order by 1;

SELECT df.TABLESPACE_NAME, d.creation_time FROM V$DATAFILE d, DBA_DATA_FILES df where d.name=df.file_name;


select df.tablespace_name "name",
    ROUND(df.bytes/(1024*1023),2) "USED(Mb)",
    ROUND(df.maxbytes/(1024*1023),2) "TOTAL(Mb)",
    ROUND((df.maxbytes - df.bytes)/(1024*1023),2) "FREE(MB)",
    ROUND(SYSDATE - d.creation_time,0) DAYS_CREATED
from Dba_data_files df, v$datafile d
where df.file_name = d.name;
order by 1;




SELECT NAME, CREATION_TIME, TS# FROM V$DATAFILE;
SELECT * FROM V$TABLESPACE;
SELECT * FROM DBA_DATA_FILES;



                            /*
                                LOGFILE SECTION
                            */
                            
                            
create or replace function date_to_unix_ts( PDate in date ) return number is
   l_unix_ts number;
begin
   l_unix_ts := ( PDate - date '1970-01-01' ) * 60 * 60 * 24;
   return l_unix_ts;
end;
/

CREATE OR REPLACE VIEW rep_tsinfo AS
SELECT
      ts.tablespace_name    "NAME",
      "SIZE(B)"/1024/1024                                  "TOTAL_SIZE",
      fr."FREE(B)"/1024/1024                               "FREE_MB",
      ("SIZE(B)" - "FREE(B)")/1024/1024                    "USED_MB"
    FROM
      (SELECT
         tablespace_name,
         SUM(bytes) "FREE(B)"
       FROM dba_free_space
       GROUP BY tablespace_name) fr,
      (SELECT
         tablespace_name,
         SUM(bytes)    "SIZE(B)",
         SUM(maxbytes) "MAX_EXT"
       FROM dba_data_files
       GROUP BY tablespace_name) df,
      (SELECT tablespace_name
       FROM dba_tablespaces) ts
    WHERE fr.tablespace_name = df.tablespace_name
          AND fr.tablespace_name = ts.tablespace_name;
          
SELECT * FROM rep_tsinfo;


                        /*
                        USERS SQL SECTION
                        */

--Tabla de los usuarios
CREATE OR REPLACE VIEW view_table_user_SQL AS
    SELECT DISTINCT au.USERNAME parseuser, vs.sql_text, vs.executions, vs.users_opening,
    to_char(to_date(vs.first_load_time, 'YYYY-MM-DD/HH24:MI:SS'),'MM/DD HH24:MI:SS') first_load_time, rows_processed, a.name command_type
    FROM v$sqlarea vs , all_users au, audit_actions a 
    where (au.user_id(+)=vs.parsing_user_id) and (executions >= 1) and vs.COMMAND_TYPE = a.ACTION
    order by USERNAME, first_load_time;

Select PARSEUSER,SQL_TEXT,EXECUTIONS,USERS_OPENING,FIRST_LOAD_TIME,ROWS_PROCESSED,COMMAND_TYPE from sys.view_table_user_SQL;

CREATE OR REPLACE FUNCTION fun_get_usernames RETURN SYS_REFCURSOR IS
    cr SYS_REFCURSOR;
BEGIN
    OPEN cr FOR
    SELECT username FROM dba_users WHERE account_status = 'OPEN' order by 1;
    RETURN cr;
END;
                                /*
                                LOGS MONITOR
                                */

/* consultas
SELECT * FROM V$LOG;
SELECT * FROM V$LOGFILE;
SELECT * FROM V$LOG_HISTORY;
*/

/*
ALTER DATABASE ADD LOGFILE MEMBER 'C:\APP\DAVID\PRODUCT\21C\ORADATA\XE\REDO04.LOG' TO GROUP 3;
*/

CREATE OR REPLACE FUNCTION fun_get_logsinfo RETURN SYS_REFCURSOR IS
    cr SYS_REFCURSOR;
BEGIN
    OPEN cr FOR
    SELECT GROUP#, MEMBERS, STATUS FROM V$LOG;
    RETURN cr;
END fun_get_logsinfo;
/

CREATE OR REPLACE FUNCTION fun_get_logMode RETURN VARCHAR2 IS
  vmode  VARCHAR2(16);
BEGIN
  SELECT LOG_MODE INTO vmode FROM V$DATABASE;
  RETURN vmode;
END fun_get_logmode;
/

--Prueba de la funcion
SELECT fun_get_logMode FROM DUAL;

CREATE OR REPLACE FUNCTION fun_get_logSwitchMinutesAvg
  RETURN NUMBER IS
  MM NUMBER;
  BEGIN
    WITH SWITCHMINUTES AS
    (
      select BASE.MINUTES, ROW_NUMBER() OVER (ORDER BY BASE.MINUTES DESC) AS ROWNUMBER
      from (select TRUNC(date_to_unix_ts(first_time)/60,2) as MINUTES FROM v$log_history) BASE
    )
    SELECT TRUNC(AVG(AA.MINUTES-BB.MINUTES),2) AS AVG_SWITCH_MINUTES INTO MM
    FROM SWITCHMINUTES AA, SWITCHMINUTES BB
    WHERE AA.ROWNUMBER = BB.ROWNUMBER-1;
    RETURN MM;
  END fun_get_logSwitchMinutesAvg;
/

--Prueba de la funcion
select  switch_minutes_avg from dual;


                        /*
                        RMAN SECTION
                        */

CREATE OR REPLACE FUNCTION fun_get_rman_backup_jobs RETURN SYS_REFCURSOR IS
  cr SYS_REFCURSOR;
BEGIN
    OPEN cr FOR
        SELECT
            SESSION_KEY,
            INPUT_TYPE,
            STATUS,
            TO_CHAR(START_TIME, 'YYYY-MM-DD HH24:MI:SS') AS START_TIME_STR,
            TO_CHAR(END_TIME, 'YYYY-MM-DD HH24:MI:SS') AS END_TIME_STR,
            INPUT_BYTES_DISPLAY,
            OUTPUT_BYTES_DISPLAY,
            ELAPSED_SECONDS,
            COMPRESSION_RATIO
        FROM
            V$RMAN_BACKUP_JOB_DETAILS
        ORDER BY
            START_TIME DESC;
    RETURN cr;
    EXCEPTION
        WHEN NO_DATA_FOUND THEN
            -- Return an empty cursor or handle as appropriate
            -- For now, returning NULL, but frontend should handle this gracefully
            RETURN NULL;
        WHEN OTHERS THEN
            -- Log error or raise notice, then return NULL
            -- DBMS_OUTPUT.PUT_LINE('Error in fun_get_rman_backup_jobs: ' || SQLERRM);
            RETURN NULL;
END fun_get_rman_backup_jobs;
/
SHOW ERROR;

CREATE OR REPLACE PROCEDURE PROC_START_RMAN_FULL_BACKUP (
    p_backup_path IN VARCHAR2,
    p_file_name_prefix IN VARCHAR2,
    p_include_controlfile IN BOOLEAN DEFAULT FALSE,
    p_include_archivelogs IN BOOLEAN DEFAULT FALSE,
    p_catalog_user IN VARCHAR2 DEFAULT NULL,
    p_catalog_password IN VARCHAR2 DEFAULT NULL,
    o_rman_script OUT CLOB,
    o_status OUT VARCHAR2,
    o_message OUT VARCHAR2
) AS
    v_rman_script CLOB;
    v_backup_format VARCHAR2(1000);
    v_connect_string VARCHAR2(200);
    v_backup_command VARCHAR2(2000);
BEGIN
    -- Basic validation for path and prefix (more robust validation can be added)
    IF p_backup_path IS NULL OR LENGTH(TRIM(p_backup_path)) = 0 OR
       p_file_name_prefix IS NULL OR LENGTH(TRIM(p_file_name_prefix)) = 0 THEN
        o_status := 'ERROR';
        o_message := 'Backup path and file name prefix must be provided.';
        RETURN;
    END IF;

    -- Construct backup format string
    v_backup_format := p_backup_path || '/%U_' || p_file_name_prefix || '.bak'; -- Adjusted for typical directory structure

    -- Construct connect string (optional catalog)
    v_connect_string := 'CONNECT TARGET /;' || CHR(10);
    IF p_catalog_user IS NOT NULL AND p_catalog_password IS NOT NULL THEN
        v_connect_string := v_connect_string || 'CONNECT CATALOG ' || DBMS_ASSERT.SIMPLE_SQL_NAME(p_catalog_user) || '/' || DBMS_ASSERT.ENQUOTE_LITERAL(p_catalog_password) || ';' || CHR(10);
    END IF;

    -- Construct backup command
    v_backup_command := 'BACKUP DATABASE';
    IF p_include_controlfile THEN
        v_backup_command := v_backup_command || ' INCLUDE CURRENT CONTROLFILE';
    END IF;
    IF p_include_archivelogs THEN
        v_backup_command := v_backup_command || ' PLUS ARCHIVELOG';
    END IF;
    v_backup_command := v_backup_command || ';';

    -- Assemble the RMAN script
    v_rman_script := v_connect_string ||
                     'RUN {' || CHR(10) ||
                     '  ALLOCATE CHANNEL ch1 DEVICE TYPE DISK FORMAT ''' || v_backup_format || ''';' || CHR(10) ||
                     '  ' || v_backup_command || CHR(10) ||
                     '  RELEASE CHANNEL ch1;' || CHR(10) ||
                     '}';

    o_rman_script := v_rman_script;
    o_status := 'SUCCESS';
    o_message := 'RMAN script generated. Execute this script via DBMS_SCHEDULER or a secure external process.';

EXCEPTION
    WHEN OTHERS THEN
        o_status := 'ERROR';
        o_message := 'Error generating RMAN script: ' || SQLERRM;
        o_rman_script := NULL;
END PROC_START_RMAN_FULL_BACKUP;
/
SHOW ERROR;

CREATE OR REPLACE PROCEDURE PROC_START_RMAN_TS_BACKUP (
    p_tablespace_name IN VARCHAR2,
    p_backup_path IN VARCHAR2,
    p_file_name_prefix IN VARCHAR2,
    p_catalog_user IN VARCHAR2 DEFAULT NULL,
    p_catalog_password IN VARCHAR2 DEFAULT NULL,
    o_rman_script OUT CLOB,
    o_status OUT VARCHAR2,
    o_message OUT VARCHAR2
) AS
    v_rman_script CLOB;
    v_backup_format VARCHAR2(1000);
    v_connect_string VARCHAR2(200);
BEGIN
    -- Basic validation
    IF p_tablespace_name IS NULL OR LENGTH(TRIM(p_tablespace_name)) = 0 OR
       p_backup_path IS NULL OR LENGTH(TRIM(p_backup_path)) = 0 OR
       p_file_name_prefix IS NULL OR LENGTH(TRIM(p_file_name_prefix)) = 0 THEN
        o_status := 'ERROR';
        o_message := 'Tablespace name, backup path, and file name prefix must be provided.';
        RETURN;
    END IF;

    v_backup_format := p_backup_path || '/%U_' || p_file_name_prefix || '_' || DBMS_ASSERT.SIMPLE_SQL_NAME(p_tablespace_name) || '.bak';

    v_connect_string := 'CONNECT TARGET /;' || CHR(10);
    IF p_catalog_user IS NOT NULL AND p_catalog_password IS NOT NULL THEN
        v_connect_string := v_connect_string || 'CONNECT CATALOG ' || DBMS_ASSERT.SIMPLE_SQL_NAME(p_catalog_user) || '/' || DBMS_ASSERT.ENQUOTE_LITERAL(p_catalog_password) || ';' || CHR(10);
    END IF;

    v_rman_script := v_connect_string ||
                     'RUN {' || CHR(10) ||
                     '  ALLOCATE CHANNEL ch1 DEVICE TYPE DISK FORMAT ''' || v_backup_format || ''';' || CHR(10) ||
                     '  BACKUP TABLESPACE ' || DBMS_ASSERT.SIMPLE_SQL_NAME(p_tablespace_name) || ';' || CHR(10) ||
                     '  RELEASE CHANNEL ch1;' || CHR(10) ||
                     '}';

    o_rman_script := v_rman_script;
    o_status := 'SUCCESS';
    o_message := 'RMAN script for tablespace generated. Execute this script via DBMS_SCHEDULER or a secure external process.';

EXCEPTION
    WHEN OTHERS THEN
        o_status := 'ERROR';
        o_message := 'Error generating RMAN tablespace script: ' || SQLERRM;
        o_rman_script := NULL;
END PROC_START_RMAN_TS_BACKUP;
/
SHOW ERROR;

-- Note: For actual execution, DBMS_SCHEDULER.CREATE_JOB with job_type 'EXECUTABLE'
-- and job_action pointing to a shell script that runs rman cmdfile=<script_file>
-- would be a more secure way than direct exec from PHP.
-- These procedures currently generate the script, which PHP will then save and execute.
-- A further refinement would be for these PL/SQL procedures to directly submit the job to DBMS_SCHEDULER.
