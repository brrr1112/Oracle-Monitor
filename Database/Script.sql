/*
SGA SECTION
*/

--Crear la tabla para insertar los datos
CREATE TABLE job_SGA_Table(USED_MB number,FREE_MB number,TOTAL_MB number,TIME date);

/*Vaciar la tabla
TRUNCATE TABLE job_SGA_Table;
*/

/*
DROP TABLE job_SGA_Table;
*/

select  USED_MB,TIME, TOTAL_MB from job_SGA_Table;

--Procedimiento SGA
Create or replace procedure prc_ins_job_SGA AS
  Begin
	Insert into job_SGA_Table(USED_MB,FREE_MB,TOTAL_MB,TIME)
	select round(used.bytes /1024/1024 ,2) used_mb
	, round(free.bytes /1024/1024 ,2) free_mb
	, (round(tot.bytes /1024/1024 ,2)) TOTAL_mb
	,current_date  Time
	from (select sum(bytes) bytes
	from v$sgastat
	where name != 'free memory') used
	, (select sum(bytes) bytes
	from v$sgastat
	where name = 'free memory') free
	, (select sum(bytes) bytes
	from v$sgastat)  tot ;
	commit;
end prc_ins_job_SGA;
  
--CREATE OR REPLACE FUNCTION fun_sga_maxsize
  
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

CREATE OR REPLACE FUNCTION fun_sga_maxsize RETURN NUMBER IS
tot number;
BEGIN
    SELECT TOTAL_MB INTO tot from job_SGA_Table FETCH FIRST 1 ROWS ONLY;
    RETURN tot;
    EXCEPTION 
        WHEN no_data_found THEN RETURN (0);
END fun_sga_maxsize;
/
show error

--Prueba de la funcion
select fun_sga_maxsize TOTAL FROM dual;

/*
TABLESPACE SECTION
*/

--Nombres de los tablespace
select tablespace_name from Dba_data_files;

CREATE OR REPLACE FUNCTION fun_get_tablespace_info(Ptsname IN VARCHAR2) RETURN SYS_REFCURSOR IS
  cr SYS_REFCURSOR;
  BEGIN
    OPEN cr FOR
    SELECT
      ts.tablespace_name,
      TRUNC("SIZE(B)", 2)                                  "BYTES_SIZE",
      TRUNC(fr."FREE(B)", 2)                               "BYTES_FREE",
      TRUNC("SIZE(B)" - "FREE(B)", 2)                      "BYTES_USED",
      TRUNC((1 - (fr."FREE(B)" / df."SIZE(B)")) * 100, 10) "PCT_USED"
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
       FROM dba_tablespaces where tablespace_name = Ptsname ) ts
    WHERE fr.tablespace_name = df.tablespace_name
          AND fr.tablespace_name = ts.tablespace_name;
    RETURN cr;
  END;
/


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

SELECT TRUNC (first_time) "Fecha", TO_CHAR (first_time, \'Dy\') "Dia", COUNT (1) "Total", ROUND (COUNT (1) / 24, 3)*10 "Promedio"
FROM gv$log_history
WHERE thread# = inst_id AND first_time > sysdate -7
GROUP BY TRUNC (first_time), inst_id, TO_CHAR (first_time, \'Dy\')
ORDER BY 1,2

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
