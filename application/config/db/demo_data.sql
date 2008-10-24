INSERT INTO `organizations` (id, name, nickname, father) VALUES (1, 'Big Government Agency', 'BGA', 0);
INSERT INTO `networks` (id, name, nickname, `desc`) VALUES (1,'Test Data Center A','TDCA','');
INSERT INTO `sources` (id, name, nickname, `desc`) VALUES (1,'Database Scanner','DB','');
INSERT INTO `systems` (id, name, nickname, organization_id, `desc`, `type`, confidentiality, integrity, availability) VALUES (1,'Test System A','TSA',1,'','GENERAL SUPPORT SYSTEM','HIGH','HIGH','HIGH'),(2,'Test System B','TSB',1,'','GENERAL SUPPORT SYSTEM','HIGH','HIGH','HIGH');
