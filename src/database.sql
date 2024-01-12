/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE TABLE IF NOT EXISTS `hl_user` (
  `id_hl_user` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(319) NOT NULL,
  `creation_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_hl_user`),
  UNIQUE KEY `email_unique` (`email`),
  KEY `email` (`email`),
  KEY `id_hl_user` (`id_hl_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `hl_user_accounts` (
  `id_hl_user_accounts` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_hl_user` bigint(20) unsigned NOT NULL,
  `account_type` enum('AMAZON','EMAIL','FACEBOOK','GOOGLE','INSTAGRAM','TWITTER') NOT NULL,
  `account_pwd` varchar(250) NOT NULL,
  `account_data` text DEFAULT NULL,
  `creation_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_hl_user_accounts`),
  UNIQUE KEY `id_hl_user_account_type` (`id_hl_user`,`account_type`),
  KEY `id_hl_user` (`id_hl_user`),
  KEY `account_type` (`account_type`),
  KEY `account_pwd` (`account_pwd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
