-- --------------------------------------------------------

--
-- Table structure for table `blocked_ips`
--

CREATE TABLE IF NOT EXISTS `blocked_ips` (
  `ip` varchar(45) NOT NULL,
  PRIMARY KEY (`ip`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_ip_range`
--

CREATE TABLE IF NOT EXISTS `blocked_ip_range` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_start` varchar(45) NOT NULL,
  `ip_end` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_range` (`ip_start`,`ip_end`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_words`
--

CREATE TABLE IF NOT EXISTS `blocked_words` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `word` (`word`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;