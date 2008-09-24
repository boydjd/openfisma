--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

DELETE FROM functions WHERE id BETWEEN 89 AND 91;

UPDATE events SET function_id=4 WHERE id=1;
UPDATE events SET function_id=4 WHERE id=2;
UPDATE events SET function_id=11 WHERE id=3;
UPDATE events SET function_id=7 WHERE id=4;
UPDATE events SET function_id=8 WHERE id=5;
UPDATE events SET function_id=9 WHERE id=6;
UPDATE events SET function_id=14 WHERE id=7;
UPDATE events SET function_id=15 WHERE id=8;
UPDATE events SET function_id=16 WHERE id=9;
UPDATE events SET function_id=17 WHERE id=10;
UPDATE events SET function_id=18 WHERE id=11;
UPDATE events SET function_id=19 WHERE id=12;
UPDATE events SET function_id=20 WHERE id=13;
UPDATE events SET function_id=21 WHERE id=14;
UPDATE events SET function_id=24 WHERE id=15;
UPDATE events SET function_id=0 WHERE id=16;
UPDATE events SET function_id=23 WHERE id=17;
UPDATE events SET function_id=25 WHERE id=18;
UPDATE events SET function_id=26 WHERE id=19;
UPDATE events SET function_id=27 WHERE id=20;
UPDATE events SET function_id=43 WHERE id=21;
UPDATE events SET function_id=44 WHERE id=22;
UPDATE events SET function_id=45 WHERE id=23;
UPDATE events SET function_id=59 WHERE id=24;
UPDATE events SET function_id=60 WHERE id=25;
UPDATE events SET function_id=61 WHERE id=26;
UPDATE events SET function_id=67 WHERE id=27;
UPDATE events SET function_id=68 WHERE id=28;
UPDATE events SET function_id=69 WHERE id=29;
UPDATE events SET function_id=72 WHERE id=30;
UPDATE events SET function_id=73 WHERE id=31;
UPDATE events SET function_id=74 WHERE id=32;
UPDATE events SET function_id=82 WHERE id=33;
UPDATE events SET function_id=83 WHERE id=34;
UPDATE events SET function_id=81 WHERE id=35;
UPDATE events SET function_id=78 WHERE id=36;
UPDATE events SET function_id=75 WHERE id=37;
UPDATE events SET function_id=76 WHERE id=38;
UPDATE events SET function_id=86 WHERE id=39;
UPDATE events SET function_id=87 WHERE id=40;
UPDATE events SET function_id=88 WHERE id=41;
UPDATE events SET function_id=79 WHERE id=42;
UPDATE events SET function_id=45 WHERE id=43;
UPDATE events SET function_id=45 WHERE id=44;
UPDATE events SET function_id=45 WHERE id=45;
UPDATE events SET function_id=1 WHERE id=49;
UPDATE events SET function_id=1 WHERE id=48;
UPDATE events SET function_id=1 WHERE id=47;
UPDATE events SET function_id=1 WHERE id=46;

DELETE FROM events WHERE id=51;
