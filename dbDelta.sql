-- See http://codex.wordpress.org/Creating_Tables_with_Plugins#Creating_or_Updating_the_Table. dbDelta function adds the following properties to the first timestamp column in the table:
--
-- mysql> desc safe_ad_network_ads;
-- +-------+-----------+------+-----+-------------------+-----------------------------+
-- | Field | Type      | Null | Key | Default           | Extra                       |
-- +-------+-----------+------+-----+-------------------+-----------------------------+
-- | rowts | timestamp | NO   |     | CURRENT_TIMESTAMP | on update CURRENT_TIMESTAMP |
-- | spot  | char(16)  | NO   | PRI | NULL              |                             |
-- +-------+-----------+------+-----+-------------------+-----------------------------+
-- 2 rows in set (0.00 sec)
--
-- The documentation does not mention this behavior of dbDelta. If you need a TIMESTAMP column for storing something different from the default value, you have to create a dummy TIMESTAMP column as a placeholder for dbDelta to save the default value, and then create the TIMESTAMP column you want.
--
-- In the following table, the default behavior is acceptable.
--
CREATE TABLE safe_ad_network_ads(
    rowts timestamp,
    site char( 16 ),
    spot char( 16 ),
    campaign char( 16 ),
    imageurl varchar( 2083 ),
    imagefile text,
    destination varchar( 2083 ),
    probability float,
    width integer,
    height integer,
    beacon text
);
