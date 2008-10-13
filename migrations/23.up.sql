--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

INSERT INTO `configurations` (`id`, `key`, `value`, `description`) VALUES 
(23, 'pass_uppercase', '1', 'Require Upper Case Characters'),
(24, 'pass_lowercase', '1', 'Require Lower Case Characters'),
(25, 'pass_numerical', '0', 'Require Numerical Characters'),
(26, 'pass_special', '0', 'Require Special Characters'),
(27, 'pass_min', '8', 'Minimum Password Length'),
(28, 'pass_max', '64', 'Maximum Password Length');

