-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: skybuild
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,'Login Failed','Attempted login with username: skybuild_admin','::1','2026-05-19 15:33:34'),(2,'Login','Admin logged in successfully','::1','2026-05-19 15:33:45'),(3,'Logout','Admin logged out','::1','2026-05-19 16:19:03');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `security_maiden` varchar(255) DEFAULT 'Cruz',
  `security_color` varchar(255) DEFAULT 'purple',
  `security_dog` varchar(255) DEFAULT 'Gerrie',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'skybuild_admin','$2y$10$NeY4GT3oL1fWW9q20hITgem28cQN0kDp5JLDcmG/S3Zt7LfyeWtx6','skybuildadmin@gmail.com','2026-05-19 15:32:46','Cruz','purple','Gerrie');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event_date` date NOT NULL,
  `event_time` varchar(50) DEFAULT '',
  `title` varchar(255) NOT NULL,
  `client_name` varchar(255) DEFAULT '',
  `description` text DEFAULT NULL,
  `color` varchar(20) DEFAULT '#64b5f6',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inquiries`
--

DROP TABLE IF EXISTS `inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inquiries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `project_type` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiries`
--

LOCK TABLES `inquiries` WRITE;
/*!40000 ALTER TABLE `inquiries` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `size` varchar(100) NOT NULL DEFAULT '',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL DEFAULT '',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=263 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,'Pipe (PVC 2 inch)','',50,'',0.00,'2026-05-19 15:32:46',NULL),(2,'Steel Rebar (10mm)','',200,'',0.00,'2026-05-19 15:32:46',NULL),(3,'Screwdriver Set','',15,'',0.00,'2026-05-19 15:32:46',NULL),(4,'Aggregate','3/8\"',110,'pcs',984.41,'2026-05-19 15:44:39',NULL),(5,'Aggregate','1/2\"',443,'pcs',990.94,'2026-05-19 15:44:39',NULL),(6,'Aggregate','3/4\"',293,'pcs',1334.36,'2026-05-19 15:44:39',NULL),(7,'Aggregate','1\"',398,'pcs',351.16,'2026-05-19 15:44:39',NULL),(8,'Aluminum sheets','4 ft × 8 ft',353,'pcs',1270.56,'2026-05-19 15:44:39',NULL),(9,'Angle bar','1\"×1\"',73,'pcs',921.98,'2026-05-19 15:44:39',NULL),(10,'Angle bar','1.5\"×1.5\"',353,'pcs',1169.40,'2026-05-19 15:44:39',NULL),(11,'Angle bar','2\"×2\"',251,'pcs',418.44,'2026-05-19 15:44:39',NULL),(12,'Angle bar','3\"×3\"',245,'pcs',724.22,'2026-05-19 15:44:39',NULL),(13,'Asphalt','sack',410,'pcs',409.16,'2026-05-19 15:44:39',NULL),(14,'Asphalt','drum',324,'pcs',1451.55,'2026-05-19 15:44:39',NULL),(15,'Asphalt','or cubic meter',325,'cu.m',1002.31,'2026-05-19 15:44:39',NULL),(16,'Bamboo','8 ft',272,'pcs',1074.18,'2026-05-19 15:44:39',NULL),(17,'Bamboo','10 ft',344,'pcs',837.38,'2026-05-19 15:44:39',NULL),(18,'Bamboo','12 ft',295,'pcs',814.99,'2026-05-19 15:44:39',NULL),(19,'Barbed wire','100 m/roll',262,'liter',293.50,'2026-05-19 15:44:39',NULL),(20,'Barbed wire','200 m/roll',381,'liter',1489.89,'2026-05-19 15:44:39',NULL),(21,'Binding wire','#16',439,'pcs',163.31,'2026-05-19 15:44:39',NULL),(22,'Binding wire','#18',64,'pcs',1331.01,'2026-05-19 15:44:39',NULL),(23,'Bolts and nuts','1/4\"',186,'pcs',725.07,'2026-05-19 15:44:39',NULL),(24,'Bolts and nuts','3/8\"',453,'pcs',333.57,'2026-05-19 15:44:39',NULL),(25,'Bolts and nuts','1/2\"',496,'pcs',833.24,'2026-05-19 15:44:39',NULL),(26,'Bolts and nuts','5/8\"',345,'pcs',699.78,'2026-05-19 15:44:39',NULL),(27,'Bricks','4\" × 8\" × 2\"',305,'pcs',1051.27,'2026-05-19 15:44:39',NULL),(28,'Cement','40 kg/bag',238,'bag',1104.18,'2026-05-19 15:44:39',NULL),(29,'Cement board','4 ft × 8 ft',425,'bag',337.61,'2026-05-19 15:44:39',NULL),(30,'Cement board','3.5 mm',447,'bag',713.19,'2026-05-19 15:44:39',NULL),(31,'Cement board','4.5 mm',293,'bag',1174.84,'2026-05-19 15:44:39',NULL),(32,'Cement board','6 mm',163,'bag',572.95,'2026-05-19 15:44:39',NULL),(33,'Ceramic tiles','20×20 cm',219,'pcs',430.18,'2026-05-19 15:44:39',NULL),(34,'Ceramic tiles','30×30 cm',442,'pcs',997.63,'2026-05-19 15:44:39',NULL),(35,'Ceramic tiles','40×40 cm',212,'pcs',885.60,'2026-05-19 15:44:39',NULL),(36,'Ceramic tiles','60×60 cm',208,'pcs',921.10,'2026-05-19 15:44:39',NULL),(37,'CHB / Hollow blocks','4\"',495,'pcs',212.43,'2026-05-19 15:44:39',NULL),(38,'CHB / Hollow blocks','5\"',179,'pcs',996.16,'2026-05-19 15:44:39',NULL),(39,'CHB / Hollow blocks','6\"',351,'pcs',185.83,'2026-05-19 15:44:39',NULL),(40,'Concrete','cubic meter',493,'cu.m',492.54,'2026-05-19 15:44:39',NULL),(41,'Copper pipe','1/2\"',457,'pcs',289.49,'2026-05-19 15:44:39',NULL),(42,'Copper pipe','3/4\"',344,'pcs',1395.55,'2026-05-19 15:44:39',NULL),(43,'Copper pipe','1\"',271,'pcs',708.85,'2026-05-19 15:44:39',NULL),(44,'Corrugated GI sheet','8 ft',84,'pcs',721.02,'2026-05-19 15:44:39',NULL),(45,'Corrugated GI sheet','10 ft',418,'pcs',1407.86,'2026-05-19 15:44:39',NULL),(46,'Corrugated GI sheet','12 ft',62,'pcs',1466.00,'2026-05-19 15:44:39',NULL),(47,'C-Purlins','2\"×3\"',247,'pcs',693.14,'2026-05-19 15:44:39',NULL),(48,'C-Purlins','2\"×4\"',50,'pcs',1420.73,'2026-05-19 15:44:39',NULL),(49,'C-Purlins','2\"×6\"',204,'pcs',630.46,'2026-05-19 15:44:39',NULL),(50,'Decking sheets','0.8 mm',322,'pcs',869.49,'2026-05-19 15:44:39',NULL),(51,'Decking sheets','1.0 mm',134,'pcs',1126.35,'2026-05-19 15:44:39',NULL),(52,'Decking sheets','1.2 mm',370,'pcs',992.92,'2026-05-19 15:44:39',NULL),(53,'Door','70×210 cm',146,'pcs',250.51,'2026-05-19 15:44:39',NULL),(54,'Door','80×210 cm',123,'pcs',484.81,'2026-05-19 15:44:39',NULL),(55,'Door','90×210 cm',304,'pcs',344.14,'2026-05-19 15:44:39',NULL),(56,'Door frame','2\"×4\"',208,'pcs',184.72,'2026-05-19 15:44:39',NULL),(57,'Door frame','2\"×6\"',267,'pcs',495.61,'2026-05-19 15:44:39',NULL),(58,'Drywall board','4 ft × 8 ft',305,'pcs',740.20,'2026-05-19 15:44:39',NULL),(59,'Drywall board','9 mm',131,'pcs',530.07,'2026-05-19 15:44:39',NULL),(60,'Drywall board','12 mm',14,'pcs',787.90,'2026-05-19 15:44:39',NULL),(61,'Electrical conduit','1/2\"',97,'pcs',130.14,'2026-05-19 15:44:39',NULL),(62,'Electrical conduit','3/4\"',167,'pcs',1169.25,'2026-05-19 15:44:39',NULL),(63,'Electrical conduit','1\"',95,'pcs',179.06,'2026-05-19 15:44:39',NULL),(64,'Electrical conduit','1.5\"',354,'pcs',630.66,'2026-05-19 15:44:39',NULL),(65,'Electrical conduit','2\"',389,'pcs',442.30,'2026-05-19 15:44:39',NULL),(66,'Electrical wire','1.5 mm²',66,'pcs',992.70,'2026-05-19 15:44:39',NULL),(67,'Electrical wire','2.0 mm²',119,'pcs',1293.73,'2026-05-19 15:44:39',NULL),(68,'Electrical wire','3.5 mm²',327,'pcs',1306.71,'2026-05-19 15:44:39',NULL),(69,'Electrical wire','5.5 mm²',454,'pcs',303.38,'2026-05-19 15:44:39',NULL),(70,'Epoxy','1 L',71,'liter',786.09,'2026-05-19 15:44:39',NULL),(71,'Epoxy','4 L',202,'liter',1058.13,'2026-05-19 15:44:39',NULL),(72,'Epoxy','16 L',13,'liter',1147.31,'2026-05-19 15:44:39',NULL),(73,'Expansion bolt','1/4\"',93,'pcs',1376.41,'2026-05-19 15:44:39',NULL),(74,'Expansion bolt','3/8\"',369,'pcs',157.22,'2026-05-19 15:44:39',NULL),(75,'Expansion bolt','1/2\"',88,'pcs',1187.70,'2026-05-19 15:44:39',NULL),(76,'Expansion bolt','5/8\"',80,'pcs',601.88,'2026-05-19 15:44:39',NULL),(77,'Fiber cement board','4 ft × 8 ft',158,'bag',989.40,'2026-05-19 15:44:39',NULL),(78,'Fiber cement board','3.5 mm',276,'bag',746.01,'2026-05-19 15:44:39',NULL),(79,'Fiber cement board','4.5 mm',369,'bag',1135.05,'2026-05-19 15:44:39',NULL),(80,'Fiber cement board','6 mm',258,'bag',123.41,'2026-05-19 15:44:39',NULL),(81,'Fiberglass insulation','1\" to 4\" thick',278,'pcs',1251.23,'2026-05-19 15:44:39',NULL),(82,'Finishing nails','1\"',310,'pcs',481.79,'2026-05-19 15:44:39',NULL),(83,'Finishing nails','1.5\"',22,'pcs',409.01,'2026-05-19 15:44:39',NULL),(84,'Finishing nails','2\"',10,'pcs',1114.21,'2026-05-19 15:44:40',NULL),(85,'Finishing nails','3\"',79,'pcs',1100.17,'2026-05-19 15:44:40',NULL),(86,'Floor tiles','30×30 cm',34,'pcs',755.11,'2026-05-19 15:44:40',NULL),(87,'Floor tiles','40×40 cm',301,'pcs',1332.11,'2026-05-19 15:44:40',NULL),(88,'Floor tiles','60×60 cm',112,'pcs',973.58,'2026-05-19 15:44:40',NULL),(89,'Furring channel','12 ft length',385,'liter',679.61,'2026-05-19 15:44:40',NULL),(90,'Galvanized iron sheet','8 ft',299,'pcs',215.68,'2026-05-19 15:44:40',NULL),(91,'Galvanized iron sheet','10 ft',409,'pcs',730.60,'2026-05-19 15:44:40',NULL),(92,'Galvanized iron sheet','12 ft',263,'pcs',1312.47,'2026-05-19 15:44:40',NULL),(93,'GI pipe','1/2\"',200,'pcs',1391.86,'2026-05-19 15:44:40',NULL),(94,'GI pipe','3/4\"',114,'pcs',414.45,'2026-05-19 15:44:40',NULL),(95,'GI pipe','1\"',473,'pcs',743.92,'2026-05-19 15:44:40',NULL),(96,'GI pipe','2\"',473,'pcs',547.67,'2026-05-19 15:44:40',NULL),(97,'GI wire','#12',336,'pcs',1390.65,'2026-05-19 15:44:40',NULL),(98,'GI wire','#14',408,'pcs',428.21,'2026-05-19 15:44:40',NULL),(99,'GI wire','#16',415,'pcs',223.63,'2026-05-19 15:44:40',NULL),(100,'Glass','3 mm',154,'pcs',1008.43,'2026-05-19 15:44:40',NULL),(101,'Glass','6 mm',33,'pcs',1441.60,'2026-05-19 15:44:40',NULL),(102,'Glass','10 mm',491,'pcs',718.67,'2026-05-19 15:44:40',NULL),(103,'Glass','12 mm',169,'pcs',1034.90,'2026-05-19 15:44:40',NULL),(104,'Gravel','3/4\"',498,'cu.m',1440.88,'2026-05-19 15:44:40',NULL),(105,'Gravel','1\"',399,'cu.m',1365.89,'2026-05-19 15:44:40',NULL),(106,'Grout','2 kg',84,'bag',1471.68,'2026-05-19 15:44:40',NULL),(107,'Grout','5 kg',123,'bag',1168.29,'2026-05-19 15:44:40',NULL),(108,'Grout','20 kg/bag',414,'bag',1049.29,'2026-05-19 15:44:40',NULL),(109,'Gypsum board','4 ft × 8 ft',450,'pcs',569.93,'2026-05-19 15:44:40',NULL),(110,'Gypsum board','9 mm',248,'pcs',982.64,'2026-05-19 15:44:40',NULL),(111,'Gypsum board','12 mm',478,'pcs',563.65,'2026-05-19 15:44:40',NULL),(112,'Hardiflex board','4 ft × 8 ft',437,'pcs',1261.43,'2026-05-19 15:44:40',NULL),(113,'Hardiflex board','3.5 mm',258,'pcs',668.67,'2026-05-19 15:44:40',NULL),(114,'Hardiflex board','4.5 mm',377,'pcs',458.24,'2026-05-19 15:44:40',NULL),(115,'Hardiflex board','6 mm',360,'pcs',1206.83,'2026-05-19 15:44:40',NULL),(116,'H-beam','100×100 mm',417,'pcs',906.55,'2026-05-19 15:44:40',NULL),(117,'H-beam','150×150 mm',349,'pcs',629.95,'2026-05-19 15:44:40',NULL),(118,'H-beam','200×200 mm',141,'pcs',981.15,'2026-05-19 15:44:40',NULL),(119,'I-beam','100 mm',344,'pcs',570.00,'2026-05-19 15:44:40',NULL),(120,'I-beam','150 mm',295,'pcs',906.97,'2026-05-19 15:44:40',NULL),(121,'I-beam','200 mm',193,'pcs',290.50,'2026-05-19 15:44:40',NULL),(122,'I-beam','250 mm',86,'pcs',1097.07,'2026-05-19 15:44:40',NULL),(123,'Insulation foam','1\"',229,'pcs',1462.93,'2026-05-19 15:44:40',NULL),(124,'Insulation foam','2\"',395,'pcs',1100.14,'2026-05-19 15:44:40',NULL),(125,'Insulation foam','3\"',43,'pcs',166.35,'2026-05-19 15:44:40',NULL),(126,'Insulation foam','4\"',179,'pcs',619.39,'2026-05-19 15:44:40',NULL),(127,'Iron bars','8 mm',248,'pcs',1469.91,'2026-05-19 15:44:40',NULL),(128,'Iron bars','10 mm',185,'pcs',144.71,'2026-05-19 15:44:40',NULL),(129,'Iron bars','12 mm',388,'pcs',1385.37,'2026-05-19 15:44:40',NULL),(130,'Iron bars','16 mm',91,'pcs',230.66,'2026-05-19 15:44:40',NULL),(131,'Iron bars','20 mm',262,'pcs',391.72,'2026-05-19 15:44:40',NULL),(132,'Joint compound','5 kg',327,'pcs',1437.51,'2026-05-19 15:44:40',NULL),(133,'Joint compound','20 kg',119,'pcs',64.29,'2026-05-19 15:44:40',NULL),(134,'Joint compound','25 kg',308,'pcs',870.42,'2026-05-19 15:44:40',NULL),(135,'Joist hanger','2\"×4\"',386,'pcs',1098.70,'2026-05-19 15:44:40',NULL),(136,'Joist hanger','2\"×6\"',81,'pcs',1274.37,'2026-05-19 15:44:40',NULL),(137,'Joist hanger','2\"×8\"',157,'pcs',801.78,'2026-05-19 15:44:40',NULL),(138,'Kiln-dried lumber','1\"×2\"',176,'pcs',993.28,'2026-05-19 15:44:40',NULL),(139,'Kiln-dried lumber','2\"×2\"',223,'pcs',466.53,'2026-05-19 15:44:40',NULL),(140,'Kiln-dried lumber','2\"×3\"',500,'pcs',373.29,'2026-05-19 15:44:40',NULL),(141,'Kiln-dried lumber','2\"×4\"',95,'pcs',1057.30,'2026-05-19 15:44:40',NULL),(142,'Laminated board','4 ft × 8 ft',389,'pcs',958.04,'2026-05-19 15:44:40',NULL),(143,'Lumber','1\"×2\"',300,'pcs',722.74,'2026-05-19 15:44:40',NULL),(144,'Lumber','2\"×2\"',385,'pcs',596.91,'2026-05-19 15:44:40',NULL),(145,'Lumber','2\"×3\"',203,'pcs',1074.10,'2026-05-19 15:44:40',NULL),(146,'Lumber','2\"×4\"',325,'pcs',1432.90,'2026-05-19 15:44:40',NULL),(147,'Lumber','2\"×6\"',266,'pcs',1000.02,'2026-05-19 15:44:40',NULL),(148,'L-angle bar','1\"×1\"',243,'pcs',572.96,'2026-05-19 15:44:40',NULL),(149,'L-angle bar','2\"×2\"',111,'pcs',891.05,'2026-05-19 15:44:40',NULL),(150,'L-angle bar','3\"×3\"',98,'pcs',779.96,'2026-05-19 15:44:40',NULL),(151,'Marine plywood','4 ft × 8 ft',130,'pcs',1208.48,'2026-05-19 15:44:40',NULL),(152,'Marine plywood','1/4\"',16,'pcs',907.04,'2026-05-19 15:44:40',NULL),(153,'Marine plywood','1/2\"',339,'pcs',837.50,'2026-05-19 15:44:40',NULL),(154,'Marine plywood','3/4\"',297,'pcs',602.45,'2026-05-19 15:44:40',NULL),(155,'Metal studs','2\"×3\"',361,'pcs',264.63,'2026-05-19 15:44:40',NULL),(156,'Metal studs','2\"×4\"',420,'pcs',811.09,'2026-05-19 15:44:40',NULL),(157,'Metal studs','12 ft length',500,'liter',419.71,'2026-05-19 15:44:40',NULL),(158,'Mortar','bag or cubic meter',80,'bag',771.84,'2026-05-19 15:44:40',NULL),(159,'Nails','1\"',203,'pcs',289.63,'2026-05-19 15:44:40',NULL),(160,'Nails','1.5\"',500,'pcs',1355.74,'2026-05-19 15:44:40',NULL),(161,'Nails','2\"',50,'pcs',511.36,'2026-05-19 15:44:40',NULL),(162,'Nails','2.5\"',260,'pcs',1111.06,'2026-05-19 15:44:40',NULL),(163,'Nails','3\"',50,'pcs',1113.80,'2026-05-19 15:44:40',NULL),(164,'Nails','4\"',422,'pcs',798.36,'2026-05-19 15:44:40',NULL),(165,'Nylon rope','6 mm',303,'pcs',619.90,'2026-05-19 15:44:40',NULL),(166,'Nylon rope','8 mm',408,'pcs',1242.91,'2026-05-19 15:44:40',NULL),(167,'Nylon rope','10 mm',247,'pcs',111.67,'2026-05-19 15:44:40',NULL),(168,'Nylon rope','12 mm',320,'pcs',939.43,'2026-05-19 15:44:40',NULL),(169,'Ordinary plywood','4 ft × 8 ft',254,'pcs',1161.85,'2026-05-19 15:44:40',NULL),(170,'Ordinary plywood','1/4\"',374,'pcs',780.21,'2026-05-19 15:44:40',NULL),(171,'Ordinary plywood','1/2\"',144,'pcs',606.32,'2026-05-19 15:44:40',NULL),(172,'Ordinary plywood','3/4\"',430,'pcs',566.06,'2026-05-19 15:44:40',NULL),(173,'Outlet box','2\"×4\"',38,'pcs',131.19,'2026-05-19 15:44:40',NULL),(174,'Outlet box','4\"×4\"',17,'pcs',866.12,'2026-05-19 15:44:40',NULL),(175,'Paint','1 L',264,'liter',1128.14,'2026-05-19 15:44:40',NULL),(176,'Paint','4 L',305,'liter',403.95,'2026-05-19 15:44:40',NULL),(177,'Paint','16 L',363,'liter',1495.47,'2026-05-19 15:44:40',NULL),(178,'PVC pipe','1/2\"',79,'pcs',171.29,'2026-05-19 15:44:40',NULL),(179,'PVC pipe','3/4\"',196,'pcs',1239.99,'2026-05-19 15:44:40',NULL),(180,'PVC pipe','1\"',301,'pcs',1187.55,'2026-05-19 15:44:40',NULL),(181,'PVC pipe','2\"',264,'pcs',565.19,'2026-05-19 15:44:40',NULL),(182,'PVC pipe','3\"',155,'pcs',912.83,'2026-05-19 15:44:40',NULL),(183,'PVC pipe','4\"',90,'pcs',253.45,'2026-05-19 15:44:40',NULL),(184,'PVC elbow','1/2\"',362,'pcs',813.77,'2026-05-19 15:44:40',NULL),(185,'PVC elbow','3/4\"',467,'pcs',306.45,'2026-05-19 15:44:40',NULL),(186,'PVC elbow','1\"',234,'pcs',977.42,'2026-05-19 15:44:40',NULL),(187,'PVC elbow','2\"',440,'pcs',986.08,'2026-05-19 15:44:40',NULL),(188,'PVC elbow','3\"',296,'pcs',620.20,'2026-05-19 15:44:40',NULL),(189,'PVC elbow','4\"',331,'pcs',1195.45,'2026-05-19 15:44:40',NULL),(190,'Polycarbonate sheet','6 mm',370,'pcs',1184.59,'2026-05-19 15:44:40',NULL),(191,'Polycarbonate sheet','8 mm',45,'pcs',1427.51,'2026-05-19 15:44:40',NULL),(192,'Polycarbonate sheet','10 mm',465,'pcs',1416.15,'2026-05-19 15:44:40',NULL),(193,'Quarry sand','cubic meter or truckload',22,'liter',491.89,'2026-05-19 15:44:40',NULL),(194,'Rebar / Deformed bar','8 mm',88,'pcs',499.09,'2026-05-19 15:44:40',NULL),(195,'Rebar / Deformed bar','10 mm',279,'pcs',1494.21,'2026-05-19 15:44:40',NULL),(196,'Rebar / Deformed bar','12 mm',347,'pcs',1162.74,'2026-05-19 15:44:40',NULL),(197,'Rebar / Deformed bar','16 mm',25,'pcs',118.87,'2026-05-19 15:44:40',NULL),(198,'Rebar / Deformed bar','20 mm',70,'pcs',1112.11,'2026-05-19 15:44:40',NULL),(199,'Rebar / Deformed bar','25 mm',89,'pcs',715.35,'2026-05-19 15:44:40',NULL),(200,'Roofing sheet','8 ft',456,'pcs',1413.95,'2026-05-19 15:44:40',NULL),(201,'Roofing sheet','10 ft',157,'pcs',1403.15,'2026-05-19 15:44:40',NULL),(202,'Roofing sheet','12 ft',440,'pcs',543.57,'2026-05-19 15:44:40',NULL),(203,'Roofing nails','1.5\"',390,'pcs',629.94,'2026-05-19 15:44:40',NULL),(204,'Roofing nails','2\"',361,'pcs',1330.45,'2026-05-19 15:44:40',NULL),(205,'Roofing nails','2.5\"',323,'pcs',579.81,'2026-05-19 15:44:40',NULL),(206,'Rubber sealant','300 ml',146,'liter',1172.79,'2026-05-19 15:44:40',NULL),(207,'Rubber sealant','600 ml',486,'liter',1378.82,'2026-05-19 15:44:40',NULL),(208,'Sand','cubic meter',74,'cu.m',950.19,'2026-05-19 15:44:40',NULL),(209,'Sealant','300 ml',440,'liter',1030.95,'2026-05-19 15:44:40',NULL),(210,'Sealant','600 ml',317,'liter',1282.39,'2026-05-19 15:44:40',NULL),(211,'Steel bars','8 mm',382,'pcs',508.16,'2026-05-19 15:44:40',NULL),(212,'Steel bars','10 mm',181,'pcs',394.75,'2026-05-19 15:44:40',NULL),(213,'Steel bars','12 mm',45,'pcs',815.22,'2026-05-19 15:44:40',NULL),(214,'Steel bars','16 mm',96,'pcs',1150.16,'2026-05-19 15:44:40',NULL),(215,'Steel bars','20 mm',73,'pcs',1085.00,'2026-05-19 15:44:40',NULL),(216,'Steel plate','4 ft × 8 ft',144,'pcs',56.91,'2026-05-19 15:44:40',NULL),(217,'Steel plate','3 mm',50,'pcs',590.72,'2026-05-19 15:44:40',NULL),(218,'Steel plate','6 mm',433,'pcs',936.98,'2026-05-19 15:44:40',NULL),(219,'Steel plate','10 mm',275,'pcs',1030.47,'2026-05-19 15:44:40',NULL),(220,'Switch box','2\"×4\"',98,'pcs',72.74,'2026-05-19 15:44:40',NULL),(221,'Switch box','4\"×4\"',167,'pcs',151.51,'2026-05-19 15:44:40',NULL),(222,'Tiles','20×20 cm',206,'pcs',1215.29,'2026-05-19 15:44:40',NULL),(223,'Tiles','30×30 cm',372,'pcs',696.95,'2026-05-19 15:44:40',NULL),(224,'Tiles','40×40 cm',359,'pcs',1297.08,'2026-05-19 15:44:40',NULL),(225,'Tiles','60×60 cm',249,'pcs',1244.62,'2026-05-19 15:44:40',NULL),(226,'Tile adhesive','20 kg/bag',409,'bag',205.16,'2026-05-19 15:44:40',NULL),(227,'Tile adhesive','25 kg/bag',206,'bag',454.08,'2026-05-19 15:44:40',NULL),(228,'Tubular steel','1\"×1\"',350,'pcs',991.65,'2026-05-19 15:44:40',NULL),(229,'Tubular steel','1\"×2\"',205,'pcs',1016.14,'2026-05-19 15:44:40',NULL),(230,'Tubular steel','2\"×2\"',322,'pcs',509.81,'2026-05-19 15:44:40',NULL),(231,'Tubular steel','2\"×3\"',158,'pcs',919.64,'2026-05-19 15:44:40',NULL),(232,'Tie wire','#16',425,'pcs',577.28,'2026-05-19 15:44:40',NULL),(233,'Tie wire','#18',143,'pcs',1298.39,'2026-05-19 15:44:40',NULL),(234,'U-channel','1\"',144,'pcs',1031.59,'2026-05-19 15:44:40',NULL),(235,'U-channel','2\"',464,'pcs',414.24,'2026-05-19 15:44:40',NULL),(236,'U-channel','3\"',395,'pcs',312.75,'2026-05-19 15:44:40',NULL),(237,'U-channel','4\"',292,'pcs',1315.30,'2026-05-19 15:44:40',NULL),(238,'Utility box','2\"×4\"',432,'pcs',1150.86,'2026-05-19 15:44:40',NULL),(239,'Utility box','4\"×4\"',379,'pcs',954.16,'2026-05-19 15:44:40',NULL),(240,'Varnish','1 L',337,'liter',960.54,'2026-05-19 15:44:40',NULL),(241,'Varnish','4 L',452,'liter',117.48,'2026-05-19 15:44:40',NULL),(242,'Vinyl tiles','12\"×12\"',53,'pcs',189.96,'2026-05-19 15:44:40',NULL),(243,'Vinyl tiles','18\"×18\"',48,'pcs',742.66,'2026-05-19 15:44:40',NULL),(244,'Wall tiles','20×20 cm',141,'pcs',1242.46,'2026-05-19 15:44:40',NULL),(245,'Wall tiles','25×40 cm',72,'pcs',460.01,'2026-05-19 15:44:40',NULL),(246,'Wall tiles','30×60 cm',158,'pcs',694.73,'2026-05-19 15:44:40',NULL),(247,'Waterproofing membrane','1 m × 10 m roll',261,'liter',1397.16,'2026-05-19 15:44:40',NULL),(248,'Welding rod','2.5 mm',166,'pcs',771.45,'2026-05-19 15:44:40',NULL),(249,'Welding rod','3.2 mm',139,'pcs',1008.66,'2026-05-19 15:44:40',NULL),(250,'Welding rod','4.0 mm',124,'pcs',164.17,'2026-05-19 15:44:40',NULL),(251,'Wood plank','1\"×6\"',45,'pcs',1197.56,'2026-05-19 15:44:40',NULL),(252,'Wood plank','1\"×8\"',133,'pcs',1101.01,'2026-05-19 15:44:40',NULL),(253,'Wood plank','1\"×10\"',20,'pcs',798.14,'2026-05-19 15:44:40',NULL),(254,'XPS insulation board','1\"',356,'pcs',338.06,'2026-05-19 15:44:40',NULL),(255,'XPS insulation board','2\"',496,'pcs',655.92,'2026-05-19 15:44:40',NULL),(256,'XPS insulation board','3\"',435,'pcs',283.40,'2026-05-19 15:44:40',NULL),(257,'Y-branch PVC fitting','2\"',63,'pcs',1162.25,'2026-05-19 15:44:40',NULL),(258,'Y-branch PVC fitting','3\"',70,'pcs',881.89,'2026-05-19 15:44:40',NULL),(259,'Y-branch PVC fitting','4\"',210,'pcs',1115.90,'2026-05-19 15:44:40',NULL),(260,'Z-bar / Z-purlin','2\"×3\"',63,'pcs',966.86,'2026-05-19 15:44:40',NULL),(261,'Z-bar / Z-purlin','2\"×4\"',447,'pcs',141.16,'2026-05-19 15:44:40',NULL),(262,'Z-bar / Z-purlin','2\"×6\"',432,'pcs',138.51,'2026-05-19 15:44:40',NULL);
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotation_items`
--

DROP TABLE IF EXISTS `quotation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotation_items` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `quotation_id` (`quotation_id`),
  CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotation_items`
--

LOCK TABLES `quotation_items` WRITE;
/*!40000 ALTER TABLE `quotation_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `folder_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `po_number` varchar(100) NOT NULL DEFAULT '',
  `signee_name` varchar(255) NOT NULL DEFAULT '',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `folder_id` (`folder_id`),
  CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `quote_folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quote_folders`
--

DROP TABLE IF EXISTS `quote_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quote_folders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `quote_folders_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `quote_folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quote_folders`
--

LOCK TABLES `quote_folders` WRITE;
/*!40000 ALTER TABLE `quote_folders` DISABLE KEYS */;
/*!40000 ALTER TABLE `quote_folders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `showcase`
--

DROP TABLE IF EXISTS `showcase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `showcase` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `showcase`
--

LOCK TABLES `showcase` WRITE;
/*!40000 ALTER TABLE `showcase` DISABLE KEYS */;
INSERT INTO `showcase` VALUES (1,'The Vineyard Manor - Twin Lakes','Located in Laurel, Batangas, this multi-building resort complex features a beautiful vineyard aesthetic, expansive balconies, and elegant hillside architecture designed to harmonize with the natural landscape.','twinlakes.png','2026-05-19 15:32:46',NULL),(2,'Three-Storey Residential House','A modern three-storey residential home featuring striking red vertical architectural accents, a spacious balcony, and secure perimeter fencing, built with high-quality materials for lasting durability.','three-storey.jpg','2026-05-19 15:32:46',NULL);
/*!40000 ALTER TABLE `showcase` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-20  1:17:02
