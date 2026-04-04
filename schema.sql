# CQRLOG schema snapshot


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
SET NAMES utf8mb4;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE='NO_AUTO_VALUE_ON_ZERO', SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Výpis tabulky call_alert
# ------------------------------------------------------------

CREATE TABLE `call_alert` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callsign` varchar(20) NOT NULL,
  `band` varchar(6) DEFAULT NULL,
  `mode` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `callsign` (`callsign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky club1
# ------------------------------------------------------------

CREATE TABLE `club1` (
  `id_club1` int(11) NOT NULL AUTO_INCREMENT,
  `club_nr` varchar(100) DEFAULT '',
  `clubcall` varchar(100) DEFAULT '',
  `fromdate` date DEFAULT NULL,
  `todate` date DEFAULT NULL,
  PRIMARY KEY (`id_club1`),
  KEY `club_nr` (`club_nr`),
  KEY `fromdate` (`fromdate`),
  KEY `todate` (`todate`),
  KEY `clubcall` (`clubcall`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky club2
# ------------------------------------------------------------

CREATE TABLE `club2` (
  `id_club2` int(11) NOT NULL AUTO_INCREMENT,
  `club_nr` varchar(100) DEFAULT '',
  `clubcall` varchar(100) DEFAULT '',
  `fromdate` date DEFAULT NULL,
  `todate` date DEFAULT NULL,
  PRIMARY KEY (`id_club2`),
  KEY `club_nr` (`club_nr`),
  KEY `fromdate` (`fromdate`),
  KEY `todate` (`todate`),
  KEY `clubcall` (`clubcall`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky club3
# ------------------------------------------------------------

CREATE TABLE `club3` (
  `id_club3` int(11) NOT NULL AUTO_INCREMENT,
  `club_nr` varchar(100) DEFAULT '',
  `clubcall` varchar(100) DEFAULT '',
  `fromdate` date DEFAULT NULL,
  `todate` date DEFAULT NULL,
  PRIMARY KEY (`id_club3`),
  KEY `club_nr` (`club_nr`),
  KEY `fromdate` (`fromdate`),
  KEY `todate` (`todate`),
  KEY `clubcall` (`clubcall`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky club4
# ------------------------------------------------------------

CREATE TABLE `club4` (
  `id_club4` int(11) NOT NULL AUTO_INCREMENT,
  `club_nr` varchar(100) DEFAULT '',
  `clubcall` varchar(100) DEFAULT '',
  `fromdate` date DEFAULT NULL,
  `todate` date DEFAULT NULL,
  PRIMARY KEY (`id_club4`),
  KEY `club_nr` (`club_nr`),
  KEY `fromdate` (`fromdate`),
  KEY `todate` (`todate`),
  KEY `clubcall` (`clubcall`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky club5
# ------------------------------------------------------------

CREATE TABLE `club5` (
  `id_club5` int(11) NOT NULL AUTO_INCREMENT,
  `club_nr` varchar(100) DEFAULT '',
  `clubcall` varchar(100) DEFAULT '',
  `fromdate` date DEFAULT NULL,
  `todate` date DEFAULT NULL,
  PRIMARY KEY (`id_club5`),
  KEY `club_nr` (`club_nr`),
  KEY `fromdate` (`fromdate`),
  KEY `todate` (`todate`),
  KEY `clubcall` (`clubcall`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky cqrlog_config
# ------------------------------------------------------------

CREATE TABLE `cqrlog_config` (
  `id_cqrlog__config` int(11) NOT NULL AUTO_INCREMENT,
  `config_file` longtext DEFAULT NULL,
  PRIMARY KEY (`id_cqrlog__config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky cqrlog_main
# ------------------------------------------------------------

CREATE TABLE `cqrlog_main` (
  `id_cqrlog_main` int(11) NOT NULL AUTO_INCREMENT,
  `qsodate` date NOT NULL,
  `time_on` varchar(5) NOT NULL,
  `time_off` varchar(5) DEFAULT '',
  `callsign` varchar(20) NOT NULL,
  `freq` decimal(10,4) NOT NULL,
  `mode` varchar(12) NOT NULL,
  `rst_s` varchar(20) DEFAULT '',
  `rst_r` varchar(20) DEFAULT '',
  `name` varchar(40) DEFAULT '',
  `qth` varchar(60) DEFAULT '',
  `qsl_s` varchar(4) DEFAULT '',
  `qsl_r` varchar(3) DEFAULT '',
  `qsl_via` varchar(30) DEFAULT '',
  `iota` varchar(6) DEFAULT '',
  `pwr` varchar(10) DEFAULT '',
  `itu` int(11) DEFAULT 0,
  `waz` int(11) DEFAULT 0,
  `loc` varchar(10) DEFAULT '',
  `my_loc` varchar(10) DEFAULT '',
  `county` varchar(30) DEFAULT '',
  `award` varchar(50) DEFAULT '',
  `remarks` varchar(200) DEFAULT '',
  `adif` int(11) DEFAULT 0,
  `band` varchar(6) DEFAULT '',
  `qso_dxcc` int(11) DEFAULT 0,
  `profile` int(11) DEFAULT 0,
  `idcall` varchar(20) DEFAULT '',
  `state` varchar(4) DEFAULT '',
  `lotw_qslsdate` date DEFAULT NULL,
  `lotw_qslrdate` date DEFAULT NULL,
  `lotw_qsls` varchar(3) NOT NULL DEFAULT '',
  `lotw_qslr` varchar(3) NOT NULL DEFAULT '',
  `cont` varchar(3) DEFAULT '',
  `qsls_date` varchar(10) DEFAULT NULL,
  `qslr_date` varchar(10) DEFAULT NULL,
  `club_nr1` varchar(100) DEFAULT '',
  `club_nr2` varchar(100) DEFAULT '',
  `club_nr3` varchar(100) DEFAULT '',
  `club_nr4` varchar(100) DEFAULT '',
  `club_nr5` varchar(100) DEFAULT '',
  `eqsl_qsl_sent` varchar(1) NOT NULL DEFAULT '',
  `eqsl_qslsdate` date DEFAULT NULL,
  `eqsl_qsl_rcvd` varchar(1) NOT NULL DEFAULT '',
  `eqsl_qslrdate` date DEFAULT NULL,
  `rxfreq` decimal(10,4) DEFAULT NULL,
  `satellite` varchar(30) DEFAULT '',
  `prop_mode` varchar(30) DEFAULT '',
  `stx` varchar(6) DEFAULT NULL,
  `srx` varchar(6) DEFAULT NULL,
  `stx_string` varchar(50) DEFAULT NULL,
  `srx_string` varchar(50) DEFAULT NULL,
  `contestname` varchar(40) DEFAULT NULL,
  `dok` varchar(12) DEFAULT NULL,
  `operator` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_cqrlog_main`),
  KEY `club_nr1` (`club_nr1`),
  KEY `club_nr2` (`club_nr2`),
  KEY `club_nr3` (`club_nr3`),
  KEY `club_nr4` (`club_nr4`),
  KEY `club_nr5` (`club_nr5`),
  KEY `main_index` (`qsodate`,`time_on`),
  KEY `callsign` (`callsign`),
  KEY `name` (`name`),
  KEY `qth` (`qth`),
  KEY `adif` (`adif`),
  KEY `idcall` (`idcall`),
  KEY `band` (`band`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky db_version
# ------------------------------------------------------------

CREATE TABLE `db_version` (
  `nr` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky dxcc_id
# ------------------------------------------------------------

CREATE TABLE `dxcc_id` (
  `id_dxcc` int(11) NOT NULL AUTO_INCREMENT,
  `adif` int(11) DEFAULT 0,
  `dxcc_ref` varchar(16) NOT NULL,
  `country` varchar(100) NOT NULL,
  PRIMARY KEY (`id_dxcc`),
  KEY `adif` (`adif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky freqmem
# ------------------------------------------------------------

CREATE TABLE `freqmem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `freq` decimal(10,4) NOT NULL,
  `mode` varchar(12) DEFAULT NULL,
  `bandwidth` int(11) NOT NULL,
  `info` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky log_changes
# ------------------------------------------------------------

CREATE TABLE `log_changes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cqrlog_main` int(11) DEFAULT NULL,
  `cmd` varchar(20) DEFAULT NULL,
  `qsodate` date DEFAULT NULL,
  `time_on` varchar(5) DEFAULT NULL,
  `callsign` varchar(20) DEFAULT NULL,
  `mode` varchar(12) DEFAULT NULL,
  `band` varchar(6) DEFAULT NULL,
  `freq` decimal(10,4) DEFAULT NULL,
  `old_qsodate` date DEFAULT NULL,
  `old_time_on` varchar(5) DEFAULT NULL,
  `old_callsign` varchar(20) DEFAULT NULL,
  `old_mode` varchar(12) DEFAULT NULL,
  `old_band` varchar(6) DEFAULT NULL,
  `old_freq` decimal(10,4) DEFAULT NULL,
  `upddeleted` int(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `id_cqrlog_main` (`id_cqrlog_main`),
  CONSTRAINT `log_changes_ibfk_1` FOREIGN KEY (`id_cqrlog_main`) REFERENCES `cqrlog_main` (`id_cqrlog_main`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky long_note
# ------------------------------------------------------------

CREATE TABLE `long_note` (
  `id_long_note` int(11) NOT NULL AUTO_INCREMENT,
  `note` longtext DEFAULT NULL,
  PRIMARY KEY (`id_long_note`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky notes
# ------------------------------------------------------------

CREATE TABLE `notes` (
  `id_notes` int(11) NOT NULL AUTO_INCREMENT,
  `callsign` varchar(20) DEFAULT '',
  `longremarks` varchar(256) DEFAULT '',
  PRIMARY KEY (`id_notes`),
  KEY `callsign` (`callsign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky profiles
# ------------------------------------------------------------

CREATE TABLE `profiles` (
  `id_profiles` int(11) NOT NULL AUTO_INCREMENT,
  `nr` int(11) NOT NULL,
  `locator` varchar(6) DEFAULT '',
  `qth` varchar(250) DEFAULT '',
  `rig` varchar(250) DEFAULT '',
  `remarks` varchar(250) DEFAULT '',
  `visible` int(11) DEFAULT 1,
  PRIMARY KEY (`id_profiles`),
  KEY `nr` (`nr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky upload_status
# ------------------------------------------------------------

CREATE TABLE `upload_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `logname` varchar(30) NOT NULL,
  `id_log_changes` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_log_changes` (`id_log_changes`),
  CONSTRAINT `upload_status_ibfk_1` FOREIGN KEY (`id_log_changes`) REFERENCES `log_changes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis tabulky version
# ------------------------------------------------------------

CREATE TABLE `version` (
  `major` int(11) DEFAULT 0,
  `minor` int(11) DEFAULT 9,
  `releas` int(11) DEFAULT 4
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;









# Výpis tabulky zipcode3
# ------------------------------------------------------------

CREATE TABLE `zipcode3` (
  `id_zipcode3` int(11) NOT NULL AUTO_INCREMENT,
  `zip` varchar(20) DEFAULT '',
  `county` varchar(100) DEFAULT '',
  PRIMARY KEY (`id_zipcode3`),
  KEY `zip` (`zip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



# Výpis zobrazení view_cqrlog_main_by_qsodate_asc
# ------------------------------------------------------------

CREATE ALGORITHM=UNDEFINED DEFINER=`cqrlog`@`%` SQL SECURITY DEFINER VIEW `view_cqrlog_main_by_qsodate_asc`
AS SELECT
   `cqrlog_main`.`id_cqrlog_main` AS `id_cqrlog_main`,
   `cqrlog_main`.`qsodate` AS `qsodate`,
   `cqrlog_main`.`time_on` AS `time_on`,
   `cqrlog_main`.`time_off` AS `time_off`,
   `cqrlog_main`.`callsign` AS `callsign`,
   `cqrlog_main`.`freq` AS `freq`,
   `cqrlog_main`.`mode` AS `mode`,
   `cqrlog_main`.`rst_s` AS `rst_s`,
   `cqrlog_main`.`rst_r` AS `rst_r`,
   `cqrlog_main`.`name` AS `name`,
   `cqrlog_main`.`qth` AS `qth`,
   `cqrlog_main`.`qsl_s` AS `qsl_s`,
   `cqrlog_main`.`qsl_r` AS `qsl_r`,
   `cqrlog_main`.`qsl_via` AS `qsl_via`,
   `cqrlog_main`.`iota` AS `iota`,
   `cqrlog_main`.`pwr` AS `pwr`,
   `cqrlog_main`.`itu` AS `itu`,
   `cqrlog_main`.`waz` AS `waz`,
   `cqrlog_main`.`loc` AS `loc`,
   `cqrlog_main`.`my_loc` AS `my_loc`,
   `cqrlog_main`.`county` AS `county`,
   `cqrlog_main`.`award` AS `award`,
   `cqrlog_main`.`remarks` AS `remarks`,
   `cqrlog_main`.`band` AS `band`,
   `dxcc_id`.`dxcc_ref` AS `dxcc_ref`,
   `cqrlog_main`.`qso_dxcc` AS `qso_dxcc`,
   `cqrlog_main`.`profile` AS `profile`,
   `cqrlog_main`.`idcall` AS `idcall`,
   `cqrlog_main`.`state` AS `state`,
   `cqrlog_main`.`lotw_qslsdate` AS `lotw_qslsdate`,
   `cqrlog_main`.`lotw_qslrdate` AS `lotw_qslrdate`,
   `cqrlog_main`.`lotw_qsls` AS `lotw_qsls`,
   `cqrlog_main`.`lotw_qslr` AS `lotw_qslr`,
   `cqrlog_main`.`cont` AS `cont`,
   `cqrlog_main`.`qsls_date` AS `qsls_date`,
   `cqrlog_main`.`qslr_date` AS `qslr_date`,
   `cqrlog_main`.`club_nr1` AS `club_nr1`,
   `cqrlog_main`.`club_nr2` AS `club_nr2`,
   `cqrlog_main`.`club_nr3` AS `club_nr3`,
   `cqrlog_main`.`club_nr4` AS `club_nr4`,
   `cqrlog_main`.`club_nr5` AS `club_nr5`,
   `cqrlog_main`.`eqsl_qsl_sent` AS `eqsl_qsl_sent`,
   `cqrlog_main`.`eqsl_qslsdate` AS `eqsl_qslsdate`,
   `cqrlog_main`.`eqsl_qsl_rcvd` AS `eqsl_qsl_rcvd`,
   `cqrlog_main`.`eqsl_qslrdate` AS `eqsl_qslrdate`,concat(`cqrlog_main`.`qsl_r`,
   `cqrlog_main`.`lotw_qslr`,
   `cqrlog_main`.`eqsl_qsl_rcvd`) AS `qslr`,
   `dxcc_id`.`country` AS `country`,
   `cqrlog_main`.`rxfreq` AS `rxfreq`,
   `cqrlog_main`.`satellite` AS `satellite`,
   `cqrlog_main`.`prop_mode` AS `prop_mode`,
   `cqrlog_main`.`srx` AS `srx`,
   `cqrlog_main`.`stx` AS `stx`,
   `cqrlog_main`.`srx_string` AS `srx_string`,
   `cqrlog_main`.`stx_string` AS `stx_string`,
   `cqrlog_main`.`contestname` AS `contestname`,
   `cqrlog_main`.`dok` AS `dok`,
   `cqrlog_main`.`operator` AS `operator`
FROM (`cqrlog_main` join `dxcc_id` on(`dxcc_id`.`adif` = `cqrlog_main`.`adif`)) order by `cqrlog_main`.`qsodate`,`cqrlog_main`.`time_on`;

# Výpis zobrazení view_cqrlog_main_by_callsign
# ------------------------------------------------------------

CREATE ALGORITHM=UNDEFINED DEFINER=`cqrlog`@`%` SQL SECURITY DEFINER VIEW `view_cqrlog_main_by_callsign`
AS SELECT
   `cqrlog_main`.`id_cqrlog_main` AS `id_cqrlog_main`,
   `cqrlog_main`.`qsodate` AS `qsodate`,
   `cqrlog_main`.`time_on` AS `time_on`,
   `cqrlog_main`.`time_off` AS `time_off`,
   `cqrlog_main`.`callsign` AS `callsign`,
   `cqrlog_main`.`freq` AS `freq`,
   `cqrlog_main`.`mode` AS `mode`,
   `cqrlog_main`.`rst_s` AS `rst_s`,
   `cqrlog_main`.`rst_r` AS `rst_r`,
   `cqrlog_main`.`name` AS `name`,
   `cqrlog_main`.`qth` AS `qth`,
   `cqrlog_main`.`qsl_s` AS `qsl_s`,
   `cqrlog_main`.`qsl_r` AS `qsl_r`,
   `cqrlog_main`.`qsl_via` AS `qsl_via`,
   `cqrlog_main`.`iota` AS `iota`,
   `cqrlog_main`.`pwr` AS `pwr`,
   `cqrlog_main`.`itu` AS `itu`,
   `cqrlog_main`.`waz` AS `waz`,
   `cqrlog_main`.`loc` AS `loc`,
   `cqrlog_main`.`my_loc` AS `my_loc`,
   `cqrlog_main`.`county` AS `county`,
   `cqrlog_main`.`award` AS `award`,
   `cqrlog_main`.`remarks` AS `remarks`,
   `cqrlog_main`.`band` AS `band`,
   `dxcc_id`.`dxcc_ref` AS `dxcc_ref`,
   `cqrlog_main`.`qso_dxcc` AS `qso_dxcc`,
   `cqrlog_main`.`profile` AS `profile`,
   `cqrlog_main`.`idcall` AS `idcall`,
   `cqrlog_main`.`state` AS `state`,
   `cqrlog_main`.`lotw_qslsdate` AS `lotw_qslsdate`,
   `cqrlog_main`.`lotw_qslrdate` AS `lotw_qslrdate`,
   `cqrlog_main`.`lotw_qsls` AS `lotw_qsls`,
   `cqrlog_main`.`lotw_qslr` AS `lotw_qslr`,
   `cqrlog_main`.`cont` AS `cont`,
   `cqrlog_main`.`qsls_date` AS `qsls_date`,
   `cqrlog_main`.`qslr_date` AS `qslr_date`,
   `cqrlog_main`.`club_nr1` AS `club_nr1`,
   `cqrlog_main`.`club_nr2` AS `club_nr2`,
   `cqrlog_main`.`club_nr3` AS `club_nr3`,
   `cqrlog_main`.`club_nr4` AS `club_nr4`,
   `cqrlog_main`.`club_nr5` AS `club_nr5`,
   `cqrlog_main`.`eqsl_qsl_sent` AS `eqsl_qsl_sent`,
   `cqrlog_main`.`eqsl_qslsdate` AS `eqsl_qslsdate`,
   `cqrlog_main`.`eqsl_qsl_rcvd` AS `eqsl_qsl_rcvd`,
   `cqrlog_main`.`eqsl_qslrdate` AS `eqsl_qslrdate`,concat(`cqrlog_main`.`qsl_r`,
   `cqrlog_main`.`lotw_qslr`,
   `cqrlog_main`.`eqsl_qsl_rcvd`) AS `qslr`,
   `dxcc_id`.`country` AS `country`,
   `cqrlog_main`.`rxfreq` AS `rxfreq`,
   `cqrlog_main`.`satellite` AS `satellite`,
   `cqrlog_main`.`prop_mode` AS `prop_mode`,
   `cqrlog_main`.`srx` AS `srx`,
   `cqrlog_main`.`stx` AS `stx`,
   `cqrlog_main`.`srx_string` AS `srx_string`,
   `cqrlog_main`.`stx_string` AS `stx_string`,
   `cqrlog_main`.`contestname` AS `contestname`,
   `cqrlog_main`.`dok` AS `dok`,
   `cqrlog_main`.`operator` AS `operator`
FROM (`cqrlog_main` join `dxcc_id` on(`dxcc_id`.`adif` = `cqrlog_main`.`adif`)) order by `cqrlog_main`.`callsign`;

# Výpis zobrazení view_cqrlog_main_by_qsodate
# ------------------------------------------------------------

CREATE ALGORITHM=UNDEFINED DEFINER=`cqrlog`@`%` SQL SECURITY DEFINER VIEW `view_cqrlog_main_by_qsodate`
AS SELECT
   `cqrlog_main`.`id_cqrlog_main` AS `id_cqrlog_main`,
   `cqrlog_main`.`qsodate` AS `qsodate`,
   `cqrlog_main`.`time_on` AS `time_on`,
   `cqrlog_main`.`time_off` AS `time_off`,
   `cqrlog_main`.`callsign` AS `callsign`,
   `cqrlog_main`.`freq` AS `freq`,
   `cqrlog_main`.`mode` AS `mode`,
   `cqrlog_main`.`rst_s` AS `rst_s`,
   `cqrlog_main`.`rst_r` AS `rst_r`,
   `cqrlog_main`.`name` AS `name`,
   `cqrlog_main`.`qth` AS `qth`,
   `cqrlog_main`.`qsl_s` AS `qsl_s`,
   `cqrlog_main`.`qsl_r` AS `qsl_r`,
   `cqrlog_main`.`qsl_via` AS `qsl_via`,
   `cqrlog_main`.`iota` AS `iota`,
   `cqrlog_main`.`pwr` AS `pwr`,
   `cqrlog_main`.`itu` AS `itu`,
   `cqrlog_main`.`waz` AS `waz`,
   `cqrlog_main`.`loc` AS `loc`,
   `cqrlog_main`.`my_loc` AS `my_loc`,
   `cqrlog_main`.`county` AS `county`,
   `cqrlog_main`.`award` AS `award`,
   `cqrlog_main`.`remarks` AS `remarks`,
   `cqrlog_main`.`band` AS `band`,
   `dxcc_id`.`dxcc_ref` AS `dxcc_ref`,
   `cqrlog_main`.`qso_dxcc` AS `qso_dxcc`,
   `cqrlog_main`.`profile` AS `profile`,
   `cqrlog_main`.`idcall` AS `idcall`,
   `cqrlog_main`.`state` AS `state`,
   `cqrlog_main`.`lotw_qslsdate` AS `lotw_qslsdate`,
   `cqrlog_main`.`lotw_qslrdate` AS `lotw_qslrdate`,
   `cqrlog_main`.`lotw_qsls` AS `lotw_qsls`,
   `cqrlog_main`.`lotw_qslr` AS `lotw_qslr`,
   `cqrlog_main`.`cont` AS `cont`,
   `cqrlog_main`.`qsls_date` AS `qsls_date`,
   `cqrlog_main`.`qslr_date` AS `qslr_date`,
   `cqrlog_main`.`club_nr1` AS `club_nr1`,
   `cqrlog_main`.`club_nr2` AS `club_nr2`,
   `cqrlog_main`.`club_nr3` AS `club_nr3`,
   `cqrlog_main`.`club_nr4` AS `club_nr4`,
   `cqrlog_main`.`club_nr5` AS `club_nr5`,
   `cqrlog_main`.`eqsl_qsl_sent` AS `eqsl_qsl_sent`,
   `cqrlog_main`.`eqsl_qslsdate` AS `eqsl_qslsdate`,
   `cqrlog_main`.`eqsl_qsl_rcvd` AS `eqsl_qsl_rcvd`,
   `cqrlog_main`.`eqsl_qslrdate` AS `eqsl_qslrdate`,concat(`cqrlog_main`.`qsl_r`,
   `cqrlog_main`.`lotw_qslr`,
   `cqrlog_main`.`eqsl_qsl_rcvd`) AS `qslr`,
   `dxcc_id`.`country` AS `country`,
   `cqrlog_main`.`rxfreq` AS `rxfreq`,
   `cqrlog_main`.`satellite` AS `satellite`,
   `cqrlog_main`.`prop_mode` AS `prop_mode`,
   `cqrlog_main`.`srx` AS `srx`,
   `cqrlog_main`.`stx` AS `stx`,
   `cqrlog_main`.`srx_string` AS `srx_string`,
   `cqrlog_main`.`stx_string` AS `stx_string`,
   `cqrlog_main`.`contestname` AS `contestname`,
   `cqrlog_main`.`dok` AS `dok`,
   `cqrlog_main`.`operator` AS `operator`
FROM (`cqrlog_main` join `dxcc_id` on(`dxcc_id`.`adif` = `cqrlog_main`.`adif`)) order by `cqrlog_main`.`qsodate` desc,`cqrlog_main`.`time_on` desc;


/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
