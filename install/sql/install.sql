-- MySQL dump 10.13  Distrib 5.5.41, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: fioriech65618
-- ------------------------------------------------------
-- Server version	5.5.41-0ubuntu0.14.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `contact`
--

DROP TABLE IF EXISTS `contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `message` text,
  `mail` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL COMMENT 'Contant site',
  `from_id` varchar(100) DEFAULT NULL,
  `to_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`) USING BTREE,
  KEY `datetime` (`datetime`) USING BTREE,
  KEY `from_id` (`from_id`),
  KEY `to_id` (`to_id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contact_parent`
--

DROP TABLE IF EXISTS `contact_parent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_parent` (
  `contact_id` int(11) NOT NULL,
  `parent_contanc_id` int(11) NOT NULL,
  PRIMARY KEY (`contact_id`,`parent_contanc_id`),
  KEY `contact_id` (`contact_id`),
  KEY `parent_contact_id` (`parent_contanc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content`
--

DROP TABLE IF EXISTS `content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id',
  `label` varchar(45) NOT NULL,
  `category_id` int(11) DEFAULT NULL COMMENT 'Category id',
  `title` varchar(200) DEFAULT NULL COMMENT 'Title',
  `abstract` varchar(200) DEFAULT NULL COMMENT 'Abstract',
  `content` text COMMENT 'Content',
  `creation_datetime` datetime DEFAULT NULL COMMENT 'Creation datetime',
  `modify_datetime` datetime DEFAULT NULL COMMENT 'Modify datetime',
  `author` varchar(100) DEFAULT NULL COMMENT 'Author',
  PRIMARY KEY (`id`),
  UNIQUE KEY `label_UNIQUE` (`label`),
  KEY `fk_content_1_idx` (`category_id`),
  CONSTRAINT `fk_content_1` FOREIGN KEY (`category_id`) REFERENCES `content_category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content_category`
--

DROP TABLE IF EXISTS `content_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'category id',
  `name` varchar(200) DEFAULT NULL COMMENT 'Category name',
  `description` text COMMENT 'category description',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dico_item`
--

DROP TABLE IF EXISTS `dico_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dico_item` (
  `id` varchar(100) NOT NULL,
  `parent_taxa_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `taxa_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`,`parent_taxa_id`),
  KEY `fk_dico_item_taxa_idx` (`taxa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Dicotomy item';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `profile`
--

DROP TABLE IF EXISTS `profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Profile data',
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='Profile';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `region`
--

DROP TABLE IF EXISTS `region`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `region` (
  `id` varchar(20) NOT NULL COMMENT 'Region id',
  `name` varchar(100) DEFAULT NULL COMMENT 'Region description',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Region description';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxa`
--

DROP TABLE IF EXISTS `taxa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxa` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id of taxonomy',
  `taxa_kind_id` int(11) DEFAULT NULL COMMENT 'Taxonomy kind',
  `name` varchar(100) DEFAULT NULL COMMENT 'Taxonomy name',
  `description` text COMMENT 'Taxonomi description',
  `creation_datetime` datetime DEFAULT NULL COMMENT 'Creation datetime',
  `change_datetime` datetime DEFAULT NULL COMMENT 'Last change datetime',
  PRIMARY KEY (`id`),
  KEY `fk_taxonomy_kind_idx` (`taxa_kind_id`),
  KEY `modidy_datetime` (`change_datetime`),
  CONSTRAINT `fk_taxonomy_kind` FOREIGN KEY (`taxa_kind_id`) REFERENCES `taxa_kind` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=275 DEFAULT CHARSET=utf8 COMMENT='Taxa';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxa_attribute`
--

DROP TABLE IF EXISTS `taxa_attribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxa_attribute` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id of taxa attribute',
  `name` varchar(100) NOT NULL COMMENT 'Taxa attribute name',
  `description` text COMMENT 'Taxa attribute desciption',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='Taxa attribute';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxa_attribute_value`
--

DROP TABLE IF EXISTS `taxa_attribute_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxa_attribute_value` (
  `id_taxa` int(11) NOT NULL COMMENT 'Id of taxa',
  `id_taxa_attribute` int(11) NOT NULL COMMENT 'Id of taxa attribute',
  `value` varchar(100) DEFAULT NULL COMMENT 'Value',
  PRIMARY KEY (`id_taxa`,`id_taxa_attribute`),
  KEY `fk_taxa_attribute_value_taxa_id` (`id_taxa`),
  KEY `fk_taxa_attribute_value_taxa_attribute` (`id_taxa_attribute`),
  KEY `taxa_attribute_value` (`value`),
  CONSTRAINT `fk_taxa_attribute_value_taxa` FOREIGN KEY (`id_taxa`) REFERENCES `taxa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_taxa_attribute_value_taxa_attribute` FOREIGN KEY (`id_taxa_attribute`) REFERENCES `taxa_attribute` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Taxa attribute value';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxa_image`
--

DROP TABLE IF EXISTS `taxa_image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxa_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id of taxa image',
  `id_taxa` int(11) DEFAULT NULL COMMENT 'Id of taxa',
  `filename` varchar(200) DEFAULT NULL COMMENT 'Filename',
  PRIMARY KEY (`id`),
  KEY `fk_taxa_image_1_idx` (`id_taxa`),
  CONSTRAINT `fk_taxa_image_1` FOREIGN KEY (`id_taxa`) REFERENCES `taxa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8 COMMENT='Taxa images';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxa_kind`
--

DROP TABLE IF EXISTS `taxa_kind`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxa_kind` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ord` int(11) DEFAULT NULL,
  `initials` varchar(5) DEFAULT NULL,
  `name` varchar(45) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  KEY `order` (`ord`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COMMENT='Taxa kind';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxa_region`
--

DROP TABLE IF EXISTS `taxa_region`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxa_region` (
  `id_taxa` int(11) NOT NULL,
  `id_region` varchar(20) NOT NULL,
  PRIMARY KEY (`id_taxa`,`id_region`),
  KEY `fk_taxa_region_region_idx` (`id_region`),
  KEY `fk_taxa_region_taxa_idx` (`id_taxa`),
  CONSTRAINT `fk_taxa_region_region` FOREIGN KEY (`id_region`) REFERENCES `region` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_taxa_region_taxa` FOREIGN KEY (`id_taxa`) REFERENCES `taxa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Association between taxa and region';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `username` varchar(100) NOT NULL,
  `password` varchar(100) DEFAULT NULL,
  `active` smallint(6) DEFAULT NULL,
  `profile_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `creation_datetime` datetime DEFAULT NULL COMMENT 'user creation datetime',
  `change_datetime` datetime DEFAULT NULL COMMENT 'user last modify date time',
  `confirm_datetime` datetime DEFAULT NULL COMMENT 'confirm datet time',
  `last_login_datetime` datetime DEFAULT NULL,
  `confirm_code` varchar(50) DEFAULT NULL COMMENT 'confirm code',
  `new_username` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`username`),
  KEY `fk_user_role_idx` (`role_id`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `user_role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='User data';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_role`
--

DROP TABLE IF EXISTS `user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_role` (
  `id` int(11) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='User role';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-03-18 17:24:42
