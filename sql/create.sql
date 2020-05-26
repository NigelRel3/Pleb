
CREATE USER 'Pleb'@'%' 
	IDENTIFIED WITH mysql_native_password AS '***';
GRANT SELECT, INSERT, UPDATE, DELETE, FILE ON *.* TO 'Pleb'@'%' 
	REQUIRE NONE WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 
	0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;
GRANT ALL PRIVILEGES ON `Pleb`.* TO 'Pleb'@'%';

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `password` varchar(100) NOT NULL,
  `uuid` varchar(36) NOT NULL,    // select uuid(); generate uuid
  `email` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `users` (`id`, `name`, `password`, `uuid`, `email`) 
	VALUES (NULL, 'admin', '$2y$10$ssTmhDVyOH/6e6Qi9JDPfeWDs1Xj48K4iU2uno6k7U.sj5Sx5YEju', 
		'55f910de-7405-11ea-ac31-0242ac110004', 'nigelrel3@yahoo.co.uk');


// User info table
CREATE TABLE `userInfo` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `infoType` varchar(10) NOT NULL,
  `info` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `auto` BOOLEAN NOT NULL,
  `template` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `userInfo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idxUserInfo` (`userID`,`infoType`);

ALTER TABLE `userInfo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `userInfo`
  ADD CONSTRAINT `fkUserID` FOREIGN KEY (`userID`) REFERENCES `users` (`id`);
  
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `templatenameIDX` (`name`);
  
delete from userInfo where userID = 1;
INSERT INTO `userInfo` (`id`, `userID`, `infoType`, `info`) VALUES
(17, 1, 'screenLayout', '{\"panes\": {\"top\": {\"size\": 50, \"panes\": {\"sidebar\": {\"class\": \"sidebar\", \"size\": \"100px\", \"orientation\": \"left\"}, \"workpanel\": {\"class\": \"workspace\", \"orientation\": \"right\"}}, \"orientation\": \"top\"}, \"console\": {\"class\": \"console\", \"size\": \"200px\", \"orientation\": \"bottom\"}}}');
              
UPDATE `userInfo` SET `info` = '{\"panes\": {\"top\": {\"size\": \"79.0997%\", \"panes\": {\"Sidebar\": {\"size\": \"24.3045%\", \"classToUse\": \"Sidebar\", \"orientation\": \"left\"}, \"Workpanel\": {\"size\": \"75.6955%\", \"classToUse\": \"WorkPanel\", \"orientation\": \"right\"}}, \"orientation\": \"top\"}, \"Console\": {\"size\": \"20.9003%\", \"classToUse\": \"Console\", \"orientation\": \"bottom\"}}}' WHERE `userInfo`.`id` = 17;

// Project base template
INSERT INTO `templates` (`id`, `name`, `auto`, `template`) VALUES (1, 'Project', true, '{ 	\"menu\" : \"Project\", \"Project\": { 		\"icon\": \"glyphicon glyphicon-tasks\", 		\"title\": \"A Project\", 		\"options\":  { 			\"open\" : true, \"editable\" : true 			}, 		\"nodes\": { 			\"Resources\": { 				\"icon\": \"glyphicon glyphicon-folder-close\", 				\"open-icon\": \"glyphicon glyphicon-folder-open\", 				\"title\": \"Resources\", 				\"options\":  { 					\"add\" : true 					}, 				\"InitialState\": \"closed\" 			}, 			\"Resource Definitions\": { 				\"icon\": \"glyphicon glyphicon-folder-close\", 				\"open-icon\": \"glyphicon glyphicon-folder-open\", 				\"title\": \"Resource Definitions\", 				\"options\":  { 					\"open\" : true, 					\"add\" : true 				} 			}, 			\"Transfer Definitions\": { 				\"icon\": \"glyphicon glyphicon-transfer\", 				\"title\": \"Transfer Definitions\", 			\"options\":  { 				\"open\" : true, 				\"add\" : true 				} 			} 		} 	}, 	\"AddIcon\": \"glyphicon glyphicon-plus\", 	\"DeletIcon\": \"glyphicon glyphicon-remove\" }');
INSERT INTO `templates` (`id`, `name`, `auto`, `template`) VALUES
(2, 'ScreenLayout', true, '{\"panes\": {\"top\": {\"size\": \"80%\", \"panes\": {\"sidebar\": {\"classToUse\": \"Sidebar\", \"size\": \"10%\", \"orientation\": \"left\"}, \"workpanel\": {\"classToUse\": \"WorkPanel\", \"size\": \"90%\", \"orientation\": \"right\"}}, \"orientation\": \"top\"}, \"console\": {\"classToUse\": \"Console\", \"size\": \"20%\", \"orientation\": \"bottom\"}}}');
