-- Database Backup
-- Generated on: 2025-06-28 06:06:10
-- Database: simacmur

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


-- Table structure for table `activity_logs`
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `target` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `activity_logs`
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('1', 'admin', 'TOGGLE_DEVICE', 'JAMUR588', 'Device dinonaktifkan', '::1', '2025-06-28 08:29:29');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('2', 'admin', 'TOGGLE_DEVICE', 'JAMUR588', 'Device diaktifkan', '::1', '2025-06-28 08:29:30');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('3', 'admin', 'DELETE_DEVICE', 'JAMUR588', 'Device dihapus oleh superadmin', '::1', '2025-06-28 08:33:14');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('4', 'superadmin', 'APPROVE_DEVICE', 'JAMUR901', 'Device disetujui oleh superadmin', '192.168.1.79', '2025-06-28 09:23:53');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('5', 'superadmin', 'APPROVE_DEVICE', 'JAMUR901', 'Device disetujui oleh superadmin', '192.168.1.79', '2025-06-28 09:24:19');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('6', 'superadmin', 'REJECT_DEVICE', 'JAMUR328', 'Device ditolak: Vv', '192.168.1.79', '2025-06-28 09:24:25');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('7', 'superadmin', 'REJECT_DEVICE', 'JAMUR368', 'Device ditolak: Hh', '192.168.1.79', '2025-06-28 09:24:29');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('8', 'superadmin', 'REJECT_DEVICE', 'JAMUR368', 'Device ditolak: Hh', '192.168.1.79', '2025-06-28 09:24:33');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('9', 'admin', 'USER_DELETE_DEVICE', 'JAMUR901', 'User menghapus device sendiri', '::1', '2025-06-28 09:24:54');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('10', 'superadmin', 'REJECT_DEVICE', 'JAMUR368', 'Device ditolak: Hh', '192.168.1.79', '2025-06-28 09:25:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('11', 'superadmin', 'DELETE_DEVICE', 'JAMUR328', 'Device dihapus oleh superadmin', '192.168.1.79', '2025-06-28 09:25:16');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('12', 'superadmin', 'DELETE_DEVICE', 'JAMUR368', 'Device dihapus oleh superadmin', '192.168.1.79', '2025-06-28 09:25:32');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('13', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:25:55');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('14', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:25:57');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('15', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:25:58');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('16', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:25:59');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('17', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:25:59');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('18', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:26:00');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('19', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:26:00');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('20', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:26:01');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('21', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:26:58');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('22', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:26:59');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('23', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:00');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('24', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:01');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('25', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:01');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('26', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:01');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('27', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:02');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('28', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:05');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('29', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:09');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('30', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('31', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('32', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('33', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('34', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('35', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('36', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('37', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('38', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('39', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('40', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('41', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dimatikan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:11');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('42', 'superadmin', 'CONTROL_RELAY', 'JAMUR001', 'Relay dihidupkan oleh superadmin', '192.168.1.79', '2025-06-28 09:27:12');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('43', 'superadmin', 'APPROVE_DEVICE', 'JAMUR836', 'Device disetujui oleh superadmin', '192.168.1.79', '2025-06-28 09:46:58');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('44', 'superadmin', 'TOGGLE_DEVICE', 'JAMUR836', 'Device dinonaktifkan', '192.168.1.79', '2025-06-28 09:47:15');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('45', 'superadmin', 'TOGGLE_DEVICE', 'JAMUR836', 'Device diaktifkan', '192.168.1.79', '2025-06-28 09:47:24');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('46', 'superadmin', 'DELETE_DEVICE', 'JAMUR836', 'Device dihapus oleh superadmin', '192.168.1.79', '2025-06-28 09:47:34');
INSERT INTO `activity_logs` (`id`, `user`, `action`, `target`, `details`, `ip_address`, `timestamp`) VALUES ('47', 'superadmin', 'DELETE_DEVICE', 'JAMUR836', 'Device dihapus oleh superadmin', '192.168.1.79', '2025-06-28 09:48:11');


-- Table structure for table `approval_requests`
DROP TABLE IF EXISTS `approval_requests`;
CREATE TABLE `approval_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_kode` varchar(20) NOT NULL,
  `device_nama` varchar(100) NOT NULL,
  `requester` varchar(50) NOT NULL,
  `request_message` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `processed_by` varchar(50) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `response_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `device_kode` (`device_kode`),
  KEY `requester` (`requester`),
  CONSTRAINT `approval_requests_ibfk_1` FOREIGN KEY (`device_kode`) REFERENCES `devices` (`kode`),
  CONSTRAINT `approval_requests_ibfk_2` FOREIGN KEY (`requester`) REFERENCES `users` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `devices`
DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `pemilik` varchar(50) NOT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` varchar(50) DEFAULT NULL,
  `status_approval` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` varchar(50) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  KEY `pemilik` (`pemilik`),
  CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`pemilik`) REFERENCES `users` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `notifications`
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `read_status` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `relays`
DROP TABLE IF EXISTS `relays`;
CREATE TABLE `relays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_device` varchar(20) NOT NULL,
  `status` int(11) DEFAULT 0,
  `terakhir_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_device` (`kode_device`),
  CONSTRAINT `relays_ibfk_1` FOREIGN KEY (`kode_device`) REFERENCES `devices` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `sensors`
DROP TABLE IF EXISTS `sensors`;
CREATE TABLE `sensors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_device` varchar(20) NOT NULL,
  `suhu` float NOT NULL,
  `kelembaban` float NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `kode_device` (`kode_device`),
  CONSTRAINT `sensors_ibfk_1` FOREIGN KEY (`kode_device`) REFERENCES `devices` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `user_deleted_devices`
DROP TABLE IF EXISTS `user_deleted_devices`;
CREATE TABLE `user_deleted_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_kode` varchar(20) NOT NULL,
  `device_nama` varchar(100) NOT NULL,
  `original_owner` varchar(50) NOT NULL,
  `deleted_by` varchar(50) NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deletion_reason` text DEFAULT NULL,
  `can_restore` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_device_kode` (`device_kode`),
  KEY `idx_deleted_by` (`deleted_by`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin','superadmin') DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `created_at`, `role`) VALUES ('1', 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', '2025-06-28 01:27:31', 'superadmin');

