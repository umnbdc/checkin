USE `php`;
-- MySQL dump 10.13  Distrib 5.6.13, for osx10.6 (i386)
--
-- Host: 127.0.0.1    Database: ballroom_finances
-- ------------------------------------------------------
-- Server version 5.6.14

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='-06:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table member
--
DROP TABLE IF EXISTS member;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE member (
  id smallint(10) NOT NULL AUTO_INCREMENT,
  first_name varchar(255) NOT NULL,
  nick_name varchar(255) DEFAULT NULL,
  last_name varchar(255) NOT NULL,  
  email varchar(255) NOT NULL,  
  join_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  referred_by varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table membership
--
DROP TABLE IF EXISTS membership;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE membership (
  id int(10) NOT NULL AUTO_INCREMENT,
  member_id smallint(10) NOT NULL,
  term varchar(255) NOT NULL,
  kind varchar(255) NOT NULL,
  PRIMARY KEY (id),
  INDEX (member_id),
  FOREIGN KEY (member_id) 
    REFERENCES member(id)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table debit_credit
--
DROP TABLE IF EXISTS debit_credit;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE debit_credit (
  id int(10) NOT NULL AUTO_INCREMENT,
  member_id smallint(10) NOT NULL,
  amount int(255) NOT NULL,
  kind varchar(255) NOT NULL,
  date_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX (member_id),
  FOREIGN KEY (member_id) 
    REFERENCES member(id)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table fee_status
--
DROP TABLE IF EXISTS fee_status;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE fee_status (
  id int(10) NOT NULL AUTO_INCREMENT,
  member_id smallint(10) NOT NULL,
  term varchar(255) NOT NULL,
  kind varchar(255) NOT NULL,
  PRIMARY KEY (id),
  INDEX (member_id),
  FOREIGN KEY (member_id) 
    REFERENCES member(id)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table waiver_status
--
DROP TABLE IF EXISTS waiver_status;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE waiver_status (
  id int(10) NOT NULL AUTO_INCREMENT,
  member_id smallint(10) NOT NULL,
  term varchar(255) NOT NULL,
  completed tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  INDEX (member_id),
  FOREIGN KEY (member_id) 
    REFERENCES member(id)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table referral
--
DROP TABLE IF EXISTS referral;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE referral (
  id int(10) NOT NULL AUTO_INCREMENT,
  referrer_id smallint(10) NOT NULL,
  referred_id smallint(10) NOT NULL,
  PRIMARY KEY (id),
  INDEX (referrer_id),
  FOREIGN KEY (referrer_id) 
    REFERENCES member(id),
  FOREIGN KEY (referred_id) 
    REFERENCES member(id)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table checkin
--
DROP TABLE IF EXISTS checkin;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE checkin (
  id int(10) NOT NULL AUTO_INCREMENT,
  member_id smallint(10) NOT NULL,
  date_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX (member_id),
  FOREIGN KEY (member_id) 
    REFERENCES member(id)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;