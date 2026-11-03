-- Tag the old gala and competition Pages on PRODUCTION so `wp ink migrate-kompetisies`
-- can find them without guessing from titles or a CSV row.
--
-- Safe to run more than once (each row is inserted only if it is not already there).
-- Adds post meta only. It changes no Page content, title, status or URL.
-- Table prefix on production is `wpjj_` (checked against the production dump).
-- Take a database backup first, as for any production change.
--
--   ink_old_gala         = year, on the gala Page
--   ink_old_gala_fotos   = year, on that gala's Fotos Page
--   ink_old_competition  = year, on an old annual competition Page
--
-- A NEW gala added before the cutover: add the same three-row pattern for its two Pages
-- (the migration still finds a gala by title if this is forgotten, and warns).

INSERT INTO wpjj_postmeta (post_id, meta_key, meta_value)
SELECT t.post_id, t.meta_key, t.meta_value
FROM (
	-- gala Pages
	SELECT 64611 AS post_id, 'ink_old_gala' AS meta_key, '2025' AS meta_value
	UNION ALL SELECT 61225, 'ink_old_gala', '2024'
	UNION ALL SELECT 57691, 'ink_old_gala', '2023'
	UNION ALL SELECT 52672, 'ink_old_gala', '2022'
	UNION ALL SELECT 47913, 'ink_old_gala', '2021'
	UNION ALL SELECT 43438, 'ink_old_gala', '2020'
	UNION ALL SELECT 37975, 'ink_old_gala', '2019'
	UNION ALL SELECT 28946, 'ink_old_gala', '2018'
	UNION ALL SELECT 17991, 'ink_old_gala', '2017'
	UNION ALL SELECT 7802,  'ink_old_gala', '2016'
	-- Fotos Pages
	UNION ALL SELECT 64688, 'ink_old_gala_fotos', '2025'
	UNION ALL SELECT 61228, 'ink_old_gala_fotos', '2024'
	UNION ALL SELECT 57753, 'ink_old_gala_fotos', '2023'
	UNION ALL SELECT 52674, 'ink_old_gala_fotos', '2022'
	UNION ALL SELECT 47916, 'ink_old_gala_fotos', '2021'
	UNION ALL SELECT 43440, 'ink_old_gala_fotos', '2020'
	UNION ALL SELECT 37984, 'ink_old_gala_fotos', '2019'
	UNION ALL SELECT 29087, 'ink_old_gala_fotos', '2018'
	UNION ALL SELECT 17993, 'ink_old_gala_fotos', '2017'
	UNION ALL SELECT 9949,  'ink_old_gala_fotos', '2016'
	-- the 2022 annual competition
	UNION ALL SELECT 49428, 'ink_old_competition', '2022'
) AS t
JOIN wpjj_posts p ON p.ID = t.post_id AND p.post_type = 'page'
WHERE NOT EXISTS (
	SELECT 1 FROM wpjj_postmeta m
	WHERE m.post_id = t.post_id AND m.meta_key = t.meta_key
);

-- Check: should return 21 rows.
SELECT post_id, meta_key, meta_value FROM wpjj_postmeta
WHERE meta_key IN ('ink_old_gala', 'ink_old_gala_fotos', 'ink_old_competition')
ORDER BY meta_key, meta_value;
