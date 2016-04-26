-- MySQL dump 10.13  Distrib 5.6.19, for osx10.7 (i386)
--
-- Host: localhost    Database: db
-- ------------------------------------------------------
-- Server version	5.5.46-0ubuntu0.14.04.2

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
  `image_id` int(11) DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lastname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `position` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isDeleted` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4C62E6383DA5256D` (`image_id`),
  CONSTRAINT `FK_4C62E6383DA5256D` FOREIGN KEY (`image_id`) REFERENCES `pim_file` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `faq`
--

DROP TABLE IF EXISTS `faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isDeleted` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mediacenter`
--

DROP TABLE IF EXISTS `mediacenter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mediacenter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `teaser` longtext COLLATE utf8_unicode_ci,
  `text` longtext COLLATE utf8_unicode_ci,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isDeleted` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mediacenter_files`
--

DROP TABLE IF EXISTS `mediacenter_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mediacenter_files` (
  `mediacenter_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`mediacenter_id`,`file_id`),
  KEY `IDX_101D4621789398A5` (`mediacenter_id`),
  KEY `IDX_101D462193CB796C` (`file_id`),
  CONSTRAINT `FK_101D4621789398A5` FOREIGN KEY (`mediacenter_id`) REFERENCES `mediacenter` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_101D462193CB796C` FOREIGN KEY (`file_id`) REFERENCES `pim_file` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isDeleted` tinyint(1) NOT NULL,
  `subtitle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isTop` tinyint(1) DEFAULT NULL,
  `date` datetime NOT NULL,
  `text` longtext COLLATE utf8_unicode_ci,
  `teaser` longtext COLLATE utf8_unicode_ci,
  `image_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1DD399503DA5256D` (`image_id`),
  CONSTRAINT `FK_1DD399503DA5256D` FOREIGN KEY (`image_id`) REFERENCES `pim_file` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `news_files`
--

DROP TABLE IF EXISTS `news_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news_files` (
  `news_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`news_id`,`file_id`),
  KEY `IDX_7C8AC707B5A459A0` (`news_id`),
  KEY `IDX_7C8AC70793CB796C` (`file_id`),
  CONSTRAINT `FK_7C8AC70793CB796C` FOREIGN KEY (`file_id`) REFERENCES `pim_file` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7C8AC707B5A459A0` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `news_images`
--

DROP TABLE IF EXISTS `news_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news_images` (
  `news_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`news_id`,`file_id`),
  KEY `IDX_6CB67D1EB5A459A0` (`news_id`),
  KEY `IDX_6CB67D1E93CB796C` (`file_id`),
  CONSTRAINT `FK_6CB67D1E93CB796C` FOREIGN KEY (`file_id`) REFERENCES `pim_file` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6CB67D1EB5A459A0` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `page`
--

DROP TABLE IF EXISTS `page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `text` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isDeleted` tinyint(1) NOT NULL,
  `sorting` int(11) NOT NULL,
  `entity` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pageheader` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pagesubheader` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isShortList` tinyint(1) DEFAULT NULL,
  `entityCondition` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entitySorting` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isHidden` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `page_images`
--

DROP TABLE IF EXISTS `page_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page_images` (
  `page_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`page_id`,`file_id`),
  KEY `IDX_8FC94874C4663E4` (`page_id`),
  KEY `IDX_8FC9487493CB796C` (`file_id`),
  CONSTRAINT `FK_8FC9487493CB796C` FOREIGN KEY (`file_id`) REFERENCES `pim_file` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_8FC94874C4663E4` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pim_file`
--

DROP TABLE IF EXISTS `pim_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isDeleted` tinyint(1) NOT NULL,
  `size` int(11) NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pim_token`
--

DROP TABLE IF EXISTS `pim_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_57A2ADCA5F37A13B` (`token`),
  KEY `IDX_57A2ADCAA76ED395` (`user_id`),
  CONSTRAINT `FK_57A2ADCAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pim_user`
--

DROP TABLE IF EXISTS `pim_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `pass` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_75A20A04E16C6B94` (`alias`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tarif`
--

DROP TABLE IF EXISTS `tarif`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tarif` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` longtext COLLATE utf8_unicode_ci,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isDeleted` tinyint(1) NOT NULL,
  `teaser` longtext COLLATE utf8_unicode_ci,
  `image_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E7189C93DA5256D` (`image_id`),
  CONSTRAINT `FK_E7189C93DA5256D` FOREIGN KEY (`image_id`) REFERENCES `pim_file` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tarif_files`
--

DROP TABLE IF EXISTS `tarif_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tarif_files` (
  `tarif_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`tarif_id`,`file_id`),
  KEY `IDX_DF01DBA9357C0A59` (`tarif_id`),
  KEY `IDX_DF01DBA993CB796C` (`file_id`),
  CONSTRAINT `FK_DF01DBA9357C0A59` FOREIGN KEY (`tarif_id`) REFERENCES `tarif` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_DF01DBA993CB796C` FOREIGN KEY (`file_id`) REFERENCES `pim_file` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tarif_images`
--

DROP TABLE IF EXISTS `tarif_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tarif_images` (
  `tarif_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`tarif_id`,`file_id`),
  KEY `IDX_5D7B78ED357C0A59` (`tarif_id`),
  KEY `IDX_5D7B78ED93CB796C` (`file_id`),
  CONSTRAINT `FK_5D7B78ED357C0A59` FOREIGN KEY (`tarif_id`) REFERENCES `tarif` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_5D7B78ED93CB796C` FOREIGN KEY (`file_id`) REFERENCES `pim_file` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tarifvorteile`
--

DROP TABLE IF EXISTS `tarifvorteile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tarifvorteile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `text` longtext COLLATE utf8_unicode_ci,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isDeleted` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_801C446D3DA5256D` (`image_id`),
  CONSTRAINT `FK_801C446D3DA5256D` FOREIGN KEY (`image_id`) REFERENCES `pim_file` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tarifvorteile_images`
--

DROP TABLE IF EXISTS `tarifvorteile_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tarifvorteile_images` (
  `tarifvorteile_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`tarifvorteile_id`,`file_id`),
  KEY `IDX_2FCDF00EA65A4B8D` (`tarifvorteile_id`),
  KEY `IDX_2FCDF00E93CB796C` (`file_id`),
  CONSTRAINT `FK_2FCDF00E93CB796C` FOREIGN KEY (`file_id`) REFERENCES `pim_file` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2FCDF00EA65A4B8D` FOREIGN KEY (`tarifvorteile_id`) REFERENCES `tarifvorteile` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-01-27 12:07:48
CREATE TABLE pim_push_token (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(200) NOT NULL, platform VARCHAR(10) NOT NULL, created DATETIME NOT NULL, UNIQUE INDEX UNIQ_5D3F25C75F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
CREATE TABLE push_notification (id INT AUTO_INCREMENT NOT NULL, text VARCHAR(255) NOT NULL, count INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, isDeleted TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
ALTER TABLE push_notification ADD title VARCHAR(40) NOT NULL, CHANGE text text VARCHAR(40) NOT NULL;
ALTER TABLE push_notification ADD object VARCHAR(40) NOT NULL;
ALTER TABLE pim_push_token ADD modified DATETIME NOT NULL, ADD isDeleted TINYINT(1) NOT NULL;
ALTER TABLE push_notification CHANGE title title VARCHAR(30) NOT NULL;
ALTER TABLE pim_file ADD isHidden TINYINT(1) NOT NULL;
ALTER TABLE pim_push_token ADD isHidden TINYINT(1) NOT NULL;
ALTER TABLE contact ADD isHidden TINYINT(1) NOT NULL;
ALTER TABLE faq ADD isHidden TINYINT(1) NOT NULL;
ALTER TABLE mediacenter ADD isHidden TINYINT(1) NOT NULL;
ALTER TABLE news ADD isHidden TINYINT(1) NOT NULL;
ALTER TABLE push_notification ADD isHidden TINYINT(1) NOT NULL;
ALTER TABLE tarif ADD isHidden TINYINT(1) NOT NULL;
ALTER TABLE tarifvorteile ADD isHidden TINYINT(1) NOT NULL;
ALTER TABLE `pim_push_token` ADD `user_id` INT(11) NULL ;
CREATE TABLE `pim_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `model_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `model_id` int(11) NOT NULL,
  `mode` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isDeleted` tinyint(1) NOT NULL,
  `isHidden` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DC4D4C18A76ED395` (`user_id`),
  CONSTRAINT `FK_DC4D4C18A76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE pim_file ADD user_id INT DEFAULT NULL;
ALTER TABLE pim_file ADD CONSTRAINT FK_74AEEA5DA76ED395 FOREIGN KEY (user_id) REFERENCES pim_user (id);
CREATE INDEX IDX_74AEEA5DA76ED395 ON pim_file (user_id);
ALTER TABLE contact ADD user_id INT DEFAULT NULL;
ALTER TABLE contact ADD CONSTRAINT FK_4C62E638A76ED395 FOREIGN KEY (user_id) REFERENCES pim_user (id);
CREATE INDEX IDX_4C62E638A76ED395 ON contact (user_id);
ALTER TABLE faq ADD user_id INT DEFAULT NULL;
ALTER TABLE faq ADD CONSTRAINT FK_E8FF75CCA76ED395 FOREIGN KEY (user_id) REFERENCES pim_user (id);
CREATE INDEX IDX_E8FF75CCA76ED395 ON faq (user_id);
ALTER TABLE mediacenter ADD user_id INT DEFAULT NULL;
ALTER TABLE mediacenter ADD CONSTRAINT FK_D5D1C309A76ED395 FOREIGN KEY (user_id) REFERENCES pim_user (id);
CREATE INDEX IDX_D5D1C309A76ED395 ON mediacenter (user_id);
ALTER TABLE news ADD user_id INT DEFAULT NULL;
ALTER TABLE news ADD CONSTRAINT FK_1DD39950A76ED395 FOREIGN KEY (user_id) REFERENCES pim_user (id);
CREATE INDEX IDX_1DD39950A76ED395 ON news (user_id);
ALTER TABLE page ADD user_id INT DEFAULT NULL;
ALTER TABLE page ADD CONSTRAINT FK_140AB620A76ED395 FOREIGN KEY (user_id) REFERENCES pim_user (id);
CREATE INDEX IDX_140AB620A76ED395 ON page (user_id);
ALTER TABLE push_notification ADD user_id INT DEFAULT NULL;
ALTER TABLE push_notification ADD CONSTRAINT FK_4ABA22EAA76ED395 FOREIGN KEY (user_id) REFERENCES pim_user (id);
CREATE INDEX IDX_4ABA22EAA76ED395 ON push_notification (user_id);
ALTER TABLE tarif ADD user_id INT DEFAULT NULL;
ALTER TABLE tarif ADD CONSTRAINT FK_E7189C9A76ED395 FOREIGN KEY (user_id) REFERENCES pim_user (id);
CREATE INDEX IDX_E7189C9A76ED395 ON tarif (user_id);
ALTER TABLE tarifvorteile ADD user_id INT DEFAULT NULL;
ALTER TABLE tarifvorteile ADD CONSTRAINT FK_801C446DA76ED395 FOREIGN KEY (user_id) REFERENCES pim_user (id);
CREATE INDEX IDX_801C446DA76ED395 ON tarifvorteile (user_id);
ALTER TABLE pim_file CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
CREATE UNIQUE INDEX UNIQ_74AEEA5DD1B862B8 ON pim_file (hash);
ALTER TABLE pim_push_token CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE faq CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE mediacenter CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE news CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE page CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE push_notification CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE tarif CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE tarifvorteile CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;

ALTER TABLE `push_notification` CHANGE COLUMN `object` `object` VARCHAR(40) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NULL ;
ALTER TABLE pim_user ADD user_id INT DEFAULT NULL, ADD created DATETIME NOT NULL, ADD modified DATETIME NOT NULL, ADD isDeleted TINYINT(1) NOT NULL, ADD isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE pim_user ADD CONSTRAINT FK_75A20A04A76ED395 FOREIGN KEY (user_id) REFERENCES pim_user (id);
CREATE INDEX IDX_75A20A04A76ED395 ON pim_user (user_id);
ALTER TABLE push_notification CHANGE object object VARCHAR(40) DEFAULT NULL;
ALTER TABLE pim_user ADD isAdmin TINYINT(1) DEFAULT NULL;
ALTER TABLE pim_user ADD views INT DEFAULT 0 NOT NULL, CHANGE isHidden isHidden TINYINT(1) NOT NULL;
ALTER TABLE pim_file ADD views INT DEFAULT 0 NOT NULL, CHANGE isHidden isHidden TINYINT(1) NOT NULL;
ALTER TABLE pim_log ADD views INT DEFAULT 0 NOT NULL;
ALTER TABLE pim_push_token ADD views INT DEFAULT 0 NOT NULL, CHANGE isHidden isHidden TINYINT(1) NOT NULL;
ALTER TABLE faq ADD views INT DEFAULT 0 NOT NULL, CHANGE isHidden isHidden TINYINT(1) NOT NULL;
ALTER TABLE mediacenter ADD views INT DEFAULT 0 NOT NULL, CHANGE isHidden isHidden TINYINT(1) NOT NULL;
ALTER TABLE news ADD views INT DEFAULT 0 NOT NULL, CHANGE isHidden isHidden TINYINT(1) NOT NULL;
ALTER TABLE page ADD views INT DEFAULT 0 NOT NULL, CHANGE text text LONGTEXT DEFAULT NULL, CHANGE isHidden isHidden TINYINT(1) NOT NULL;
ALTER TABLE push_notification ADD views INT DEFAULT 0 NOT NULL;
ALTER TABLE tarif ADD views INT DEFAULT 0 NOT NULL, CHANGE isHidden isHidden TINYINT(1) NOT NULL;
ALTER TABLE tarifvorteile ADD views INT DEFAULT 0 NOT NULL, CHANGE isHidden isHidden TINYINT(1) NOT NULL;
ALTER TABLE pim_user CHANGE views views INT DEFAULT 0;
ALTER TABLE pim_file CHANGE views views INT DEFAULT 0;
ALTER TABLE pim_log CHANGE views views INT DEFAULT 0;
ALTER TABLE pim_push_token CHANGE views views INT DEFAULT 0;
ALTER TABLE push_notification CHANGE views views INT DEFAULT 0;
ALTER TABLE pim_user CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE pim_file CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE pim_push_token CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE faq CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE mediacenter CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE news CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE page CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE tarif CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE tarifvorteile CHANGE isHidden isHidden TINYINT(1) DEFAULT NULL;
ALTER TABLE faq CHANGE views views INT DEFAULT 0;
ALTER TABLE mediacenter CHANGE views views INT DEFAULT 0;
ALTER TABLE news CHANGE views views INT DEFAULT 0;
ALTER TABLE page CHANGE views views INT DEFAULT 0;
ALTER TABLE tarif CHANGE views views INT DEFAULT 0;
ALTER TABLE tarifvorteile CHANGE views views INT DEFAULT 0;