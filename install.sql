
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
-- Table structure for table `pim_file`
--

DROP TABLE IF EXISTS `pim_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_file` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `folder_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usercreated_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `altText` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` int(11) NOT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `isHidden` tinyint(1) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `views` int(11) DEFAULT '0',
  `isIntern` tinyint(1) NOT NULL,
  `users` longtext COLLATE utf8_unicode_ci,
  `groups` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_unique` (`name`,`folder_id`),
  KEY `IDX_74AEEA5D162CB942` (`folder_id`),
  KEY `IDX_74AEEA5DA76ED395` (`user_id`),
  KEY `IDX_74AEEA5D139C32BD` (`usercreated_id`),
  CONSTRAINT `FK_74AEEA5D139C32BD` FOREIGN KEY (`usercreated_id`) REFERENCES `pim_user` (`id`),
  CONSTRAINT `FK_74AEEA5D162CB942` FOREIGN KEY (`folder_id`) REFERENCES `pim_folder` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_74AEEA5DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_file`
--

--
-- Table structure for table `pim_file_tags`
--

DROP TABLE IF EXISTS `pim_file_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_file_tags` (
  `file_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `tag_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`file_id`,`tag_id`),
  KEY `IDX_D36D828393CB796C` (`file_id`),
  KEY `IDX_D36D8283BAD26311` (`tag_id`),
  CONSTRAINT `FK_D36D828393CB796C` FOREIGN KEY (`file_id`) REFERENCES `pim_file` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D36D8283BAD26311` FOREIGN KEY (`tag_id`) REFERENCES `pim_tag` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_file_tags`
--

LOCK TABLES `pim_file_tags` WRITE;
/*!40000 ALTER TABLE `pim_file_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `pim_file_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pim_folder`
--

DROP TABLE IF EXISTS `pim_folder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_folder` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_26105E4BBF396750` FOREIGN KEY (`id`) REFERENCES `pim_tree` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_folder`
--

LOCK TABLES `pim_folder` WRITE;
/*!40000 ALTER TABLE `pim_folder` DISABLE KEYS */;
/*!40000 ALTER TABLE `pim_folder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pim_group`
--

DROP TABLE IF EXISTS `pim_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_group` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usercreated_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `tokenTimeout` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `views` int(11) DEFAULT '0',
  `isIntern` tinyint(1) NOT NULL,
  `users` longtext COLLATE utf8_unicode_ci,
  `groups` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_655548345E237E06` (`name`),
  KEY `IDX_65554834A76ED395` (`user_id`),
  KEY `IDX_65554834139C32BD` (`usercreated_id`),
  CONSTRAINT `FK_65554834139C32BD` FOREIGN KEY (`usercreated_id`) REFERENCES `pim_user` (`id`),
  CONSTRAINT `FK_65554834A76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_group`
--


--
-- Table structure for table `pim_log`
--

DROP TABLE IF EXISTS `pim_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_log` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usercreated_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isHidden` tinyint(1) DEFAULT NULL,
  `model_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `model_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `model_label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mode` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `views` int(11) DEFAULT '0',
  `isIntern` tinyint(1) NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `users` longtext COLLATE utf8_unicode_ci,
  `groups` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_DC4D4C18A76ED395` (`user_id`),
  KEY `IDX_DC4D4C18139C32BD` (`usercreated_id`),
  CONSTRAINT `FK_DC4D4C18139C32BD` FOREIGN KEY (`usercreated_id`) REFERENCES `pim_user` (`id`),
  CONSTRAINT `FK_DC4D4C18A76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_log`
--


--
-- Table structure for table `pim_permission`
--

DROP TABLE IF EXISTS `pim_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_permission` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `group_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usercreated_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entityName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `readable` int(11) NOT NULL,
  `writable` int(11) NOT NULL,
  `deletable` int(11) NOT NULL,
  `extended` longtext COLLATE utf8_unicode_ci,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `views` int(11) DEFAULT '0',
  `isIntern` tinyint(1) NOT NULL,
  `users` longtext COLLATE utf8_unicode_ci,
  `groups` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_ECCAA4ECFE54D947` (`group_id`),
  KEY `IDX_ECCAA4ECA76ED395` (`user_id`),
  KEY `IDX_ECCAA4EC139C32BD` (`usercreated_id`),
  CONSTRAINT `FK_ECCAA4EC139C32BD` FOREIGN KEY (`usercreated_id`) REFERENCES `pim_user` (`id`),
  CONSTRAINT `FK_ECCAA4ECA76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`),
  CONSTRAINT `FK_ECCAA4ECFE54D947` FOREIGN KEY (`group_id`) REFERENCES `pim_group` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_permission`
--

--
-- Table structure for table `pim_push_token`
--

DROP TABLE IF EXISTS `pim_push_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_push_token` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usercreated_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `token` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `platform` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `views` int(11) DEFAULT '0',
  `isIntern` tinyint(1) NOT NULL,
  `users` longtext COLLATE utf8_unicode_ci,
  `groups` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_5D3F25C75F37A13B` (`token`),
  KEY `IDX_5D3F25C7A76ED395` (`user_id`),
  KEY `IDX_5D3F25C7139C32BD` (`usercreated_id`),
  CONSTRAINT `FK_5D3F25C7139C32BD` FOREIGN KEY (`usercreated_id`) REFERENCES `pim_user` (`id`),
  CONSTRAINT `FK_5D3F25C7A76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_push_token`
--

LOCK TABLES `pim_push_token` WRITE;
/*!40000 ALTER TABLE `pim_push_token` DISABLE KEYS */;
/*!40000 ALTER TABLE `pim_push_token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pim_tag`
--

DROP TABLE IF EXISTS `pim_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_tag` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usercreated_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `views` int(11) DEFAULT '0',
  `isIntern` tinyint(1) NOT NULL,
  `users` longtext COLLATE utf8_unicode_ci,
  `groups` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_50FB935EA76ED395` (`user_id`),
  KEY `IDX_50FB935E139C32BD` (`usercreated_id`),
  CONSTRAINT `FK_50FB935E139C32BD` FOREIGN KEY (`usercreated_id`) REFERENCES `pim_user` (`id`),
  CONSTRAINT `FK_50FB935EA76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_tag`
--

LOCK TABLES `pim_tag` WRITE;
/*!40000 ALTER TABLE `pim_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `pim_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pim_thumbnail_setting`
--

DROP TABLE IF EXISTS `pim_thumbnail_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_thumbnail_setting` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usercreated_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alias` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `doCut` tinyint(1) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `percent` int(11) DEFAULT NULL,
  `backgroundColor` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `forceJpeg` tinyint(1) DEFAULT NULL,
  `isResponsive` tinyint(1) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `views` int(11) DEFAULT '0',
  `isIntern` tinyint(1) NOT NULL,
  `users` longtext COLLATE utf8_unicode_ci,
  `groups` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_F4B802E8E16C6B94` (`alias`),
  KEY `IDX_F4B802E8A76ED395` (`user_id`),
  KEY `IDX_F4B802E8139C32BD` (`usercreated_id`),
  CONSTRAINT `FK_F4B802E8139C32BD` FOREIGN KEY (`usercreated_id`) REFERENCES `pim_user` (`id`),
  CONSTRAINT `FK_F4B802E8A76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_thumbnail_setting`
--

LOCK TABLES `pim_thumbnail_setting` WRITE;
/*!40000 ALTER TABLE `pim_thumbnail_setting` DISABLE KEYS */;
INSERT INTO `pim_thumbnail_setting` VALUES ('b2647d5a-b618-11e6-b471-0800279e0795',NULL,NULL,'pim_list',1,200,200,NULL,NULL,0,0,'2016-11-29 10:46:26','2016-11-29 10:46:26',NULL,1,'',''),('b26485e1-b618-11e6-b471-0800279e0795',NULL,NULL,'pim_small',0,320,NULL,NULL,NULL,0,0,'2016-11-29 10:46:26','2016-11-29 10:46:26',NULL,1,'','');
/*!40000 ALTER TABLE `pim_thumbnail_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pim_token`
--

DROP TABLE IF EXISTS `pim_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `token` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `referrer` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_57A2ADCA5F37A13B` (`token`),
  KEY `IDX_57A2ADCAA76ED395` (`user_id`),
  CONSTRAINT `FK_57A2ADCAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_token`
--


--
-- Table structure for table `pim_tree`
--

DROP TABLE IF EXISTS `pim_tree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_tree` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `parent_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usercreated_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sorting` int(11) DEFAULT '0',
  `isActive` tinyint(1) DEFAULT '1',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `views` int(11) DEFAULT '0',
  `isIntern` tinyint(1) NOT NULL,
  `dtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `users` longtext COLLATE utf8_unicode_ci,
  `groups` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_4F0F8291727ACA70` (`parent_id`),
  KEY `IDX_4F0F8291A76ED395` (`user_id`),
  KEY `IDX_4F0F8291139C32BD` (`usercreated_id`),
  CONSTRAINT `FK_4F0F8291139C32BD` FOREIGN KEY (`usercreated_id`) REFERENCES `pim_user` (`id`),
  CONSTRAINT `FK_4F0F8291727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `pim_tree` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_4F0F8291A76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_tree`
--

LOCK TABLES `pim_tree` WRITE;
/*!40000 ALTER TABLE `pim_tree` DISABLE KEYS */;
/*!40000 ALTER TABLE `pim_tree` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pim_user`
--

DROP TABLE IF EXISTS `pim_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pim_user` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `group_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usercreated_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isAdmin` tinyint(1) DEFAULT NULL,
  `alias` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `pass` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `isActive` tinyint(1) DEFAULT NULL,
  `salt` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `views` int(11) DEFAULT '0',
  `isIntern` tinyint(1) NOT NULL,
  `users` longtext COLLATE utf8_unicode_ci,
  `groups` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_75A20A04E16C6B94` (`alias`),
  KEY `IDX_75A20A04FE54D947` (`group_id`),
  KEY `IDX_75A20A04A76ED395` (`user_id`),
  KEY `IDX_75A20A04139C32BD` (`usercreated_id`),
  CONSTRAINT `FK_75A20A04139C32BD` FOREIGN KEY (`usercreated_id`) REFERENCES `pim_user` (`id`),
  CONSTRAINT `FK_75A20A04A76ED395` FOREIGN KEY (`user_id`) REFERENCES `pim_user` (`id`),
  CONSTRAINT `FK_75A20A04FE54D947` FOREIGN KEY (`group_id`) REFERENCES `pim_group` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pim_user`
--

LOCK TABLES `pim_user` WRITE;
/*!40000 ALTER TABLE `pim_user` DISABLE KEYS */;
INSERT INTO `pim_user` VALUES ('b25f8050-b618-11e6-b471-0800279e0795',NULL,NULL,NULL,1,'admin','1571ceeba1a88ae21ab5f04e5cd0e8ae8554058893befbc00ee7d9aa0e0299af',1,'0a49204aa4d4df91ab60498b09a7e0c0d65df2fd625d3ee669222e83d871161b','2016-11-29 10:46:26','2016-11-29 10:46:26',NULL,0,'','');
/*!40000 ALTER TABLE `pim_user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

