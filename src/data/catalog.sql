SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";



CREATE TABLE gpsr (
  uid varchar(64) NOT NULL PRIMARY KEY,
  status int(1) DEFAULT NULL,
  brand VARCHAR(255) DEFAULT NULL,
  company VARCHAR(255) DEFAULT NULL,
  street VARCHAR(255) DEFAULT NULL,
  country VARCHAR(100) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  homepage VARCHAR(255) DEFAULT NULL,
  support_url VARCHAR(255) DEFAULT NULL,
  support_email VARCHAR(255) DEFAULT NULL,
  support_hotline VARCHAR(100) DEFAULT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created datetime DEFAULT NULL,
  updated datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `catalog` (
  `uid` varchar(64) NOT NULL PRIMARY KEY,
  `status` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `title` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `manufacturer` text DEFAULT NULL,
  `mpn` varchar(64) DEFAULT NULL,
  `ean` varchar(64) DEFAULT NULL,
  `taric` varchar(10) DEFAULT NULL,
  `unspc` varchar(8) DEFAULT NULL,
  `eclass` varchar(11) DEFAULT NULL,
  `weeenr` int(11) DEFAULT NULL,
  `tax` int(11) DEFAULT NULL,
  `category1` varchar(255) DEFAULT NULL,
  `category2` varchar(255) DEFAULT NULL,
  `category3` varchar(255) DEFAULT NULL,
  `category4` varchar(255) DEFAULT NULL,
  `category5` varchar(255) DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT NULL,
  `width` decimal(10,3) DEFAULT NULL,
  `depth` decimal(10,3) DEFAULT NULL,
  `height` decimal(10,3) DEFAULT NULL,
  `volweight` decimal(10,3) DEFAULT NULL,
  `iseol` tinyint(1) DEFAULT NULL,
  `eoldate` datetime DEFAULT NULL,
  `minorderqty` int(11) DEFAULT NULL,
  `maxorderqty` int(11) DEFAULT NULL,
  `copyrightcharge` decimal(10,2) DEFAULT NULL,
  `shipping` int(11) DEFAULT NULL,
  `sku` varchar(64) DEFAULT NULL,
  `iscondition` int(11) DEFAULT NULL,
  `availability` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `stocketa` date DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `catalog`
  ADD PRIMARY KEY (`uid`);
ALTER TABLE `catalog` ADD FULLTEXT KEY `ft_all` (`title`,`description`,`manufacturer`,`mpn`,`ean`,`category1`,`category2`,`category3`,`category4`,`category5`,`sku`);
ALTER TABLE `catalog` ADD FULLTEXT KEY `ft_title` (`title`);
ALTER TABLE `catalog` ADD FULLTEXT KEY `ft_description` (`description`);
ALTER TABLE `catalog` ADD FULLTEXT KEY `ft_manufacturer` (`manufacturer`);
ALTER TABLE `catalog` ADD FULLTEXT KEY `ft_mpn` (`mpn`);
ALTER TABLE `catalog` ADD FULLTEXT KEY `ft_ean` (`ean`);
ALTER TABLE `catalog` ADD FULLTEXT KEY `ft_categories` (`category1`,`category2`,`category3`,`category4`,`category5`);
ALTER TABLE `catalog` ADD FULLTEXT KEY `ft_sku` (`sku`);
COMMIT;

----
-- Refresh Indizes
----
-- bestehende Indizes löschen
ALTER TABLE catalog DROP INDEX ft_all;
ALTER TABLE catalog DROP INDEX ft_title;
ALTER TABLE catalog DROP INDEX ft_description;
ALTER TABLE catalog DROP INDEX ft_manufacturer;
ALTER TABLE catalog DROP INDEX ft_mpn;
ALTER TABLE catalog DROP INDEX ft_ean;
ALTER TABLE catalog DROP INDEX ft_categories;
ALTER TABLE catalog DROP INDEX ft_sku;

-- Hauptindex (kombiniert)
ALTER TABLE catalog ADD FULLTEXT ft_all ( title, description, manufacturer, mpn, ean, category1, category2, category3, category4, category5, sku );

-- Einzelindizes (Gewichtung)
ALTER TABLE catalog ADD FULLTEXT ft_title (title);
ALTER TABLE catalog ADD FULLTEXT ft_description (description);
ALTER TABLE catalog ADD FULLTEXT ft_manufacturer (manufacturer);
ALTER TABLE catalog ADD FULLTEXT ft_mpn (mpn);
ALTER TABLE catalog ADD FULLTEXT ft_ean (ean);
ALTER TABLE catalog ADD FULLTEXT ft_categories ( category1, category2, category3, category4, category5 );
ALTER TABLE catalog ADD FULLTEXT ft_sku (sku);

-- Optimierung (optional)
ANALYZE TABLE catalog;
