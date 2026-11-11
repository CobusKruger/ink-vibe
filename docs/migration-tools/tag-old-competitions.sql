-- Tag the old competition Pages on PRODUCTION so `wp ink migrate-kompetisies`
-- can find them without guessing from titles or a CSV row.
--
-- Safe to run more than once (each row is inserted only if it is not already there).
-- Adds post meta only. It changes no Page content, title, status or URL.
-- Table prefix on production is `wpjj_` (checked against the production dump).
-- Take a database backup first, as for any production change.
--
-- The value of every tag is the year. Roles (owner rulings 2026-11-09 and 2026-11-11):
--
--   Imported by the migration (the Page is then trashed and redirected):
--     ink_old_uitslae                   the results Page: overview of the gala evening and the
--                                       announced winners, posted after the gala. Its body is reused
--                                       as it is on that year's results page.
--     ink_old_gala_fotos                the gala photos ("Fotos") Page.
--
--   Kept live at their own addresses (marked only: never imported, trashed or redirected;
--   the owner decides their categories later):
--     ink_old_kompetisie_aankondiging   competition announcement
--     ink_old_gala_aankondiging         gala announcement
--     ink_old_kompetisie_sluiting       competition closure notice
--     ink_old_gala_herinnering          gala reminder
--
-- Retired tags: `ink_old_gala` (the Pages it marked are results Pages: now ink_old_uitslae) and
-- `ink_old_competition` (the 2022 announcement 49428: now ink_old_kompetisie_aankondiging).
-- Neither was ever applied on production. 2016: the results Page is 9878; 7802 is its duplicate
-- and is deliberately NOT tagged.
--
-- A NEW competition added before the cutover: add the same rows for its Pages (the migration
-- still finds a results Page by title if its tag is forgotten, and warns).

INSERT INTO wpjj_postmeta (post_id, meta_key, meta_value)
SELECT t.post_id, t.meta_key, t.meta_value
FROM (
	-- results Pages
	SELECT 64611 AS post_id, 'ink_old_uitslae' AS meta_key, '2025' AS meta_value
	UNION ALL SELECT 61225, 'ink_old_uitslae', '2024'
	UNION ALL SELECT 57691, 'ink_old_uitslae', '2023'
	UNION ALL SELECT 52672, 'ink_old_uitslae', '2022'
	UNION ALL SELECT 47913, 'ink_old_uitslae', '2021'
	UNION ALL SELECT 43438, 'ink_old_uitslae', '2020'
	UNION ALL SELECT 37975, 'ink_old_uitslae', '2019'
	UNION ALL SELECT 28946, 'ink_old_uitslae', '2018'
	UNION ALL SELECT 17991, 'ink_old_uitslae', '2017'
	UNION ALL SELECT 9878,  'ink_old_uitslae', '2016'
	-- gala photos Pages
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
	-- competition announcements (kept live; none for 2019 or 2016)
	UNION ALL SELECT 65779, 'ink_old_kompetisie_aankondiging', '2026'
	UNION ALL SELECT 62712, 'ink_old_kompetisie_aankondiging', '2025'
	UNION ALL SELECT 59124, 'ink_old_kompetisie_aankondiging', '2024'
	UNION ALL SELECT 54585, 'ink_old_kompetisie_aankondiging', '2023'
	UNION ALL SELECT 49428, 'ink_old_kompetisie_aankondiging', '2022'
	UNION ALL SELECT 44831, 'ink_old_kompetisie_aankondiging', '2021'
	UNION ALL SELECT 39633, 'ink_old_kompetisie_aankondiging', '2020'
	UNION ALL SELECT 18477, 'ink_old_kompetisie_aankondiging', '2018'
	UNION ALL SELECT 15351, 'ink_old_kompetisie_aankondiging', '2017'
	-- competition closure notice (kept live)
	UNION ALL SELECT 49440, 'ink_old_kompetisie_sluiting', '2022'
	-- gala announcements (kept live)
	UNION ALL SELECT 57247, 'ink_old_gala_aankondiging', '2023'
	UNION ALL SELECT 27697, 'ink_old_gala_aankondiging', '2018'
	UNION ALL SELECT 17109, 'ink_old_gala_aankondiging', '2017'
	UNION ALL SELECT 5945,  'ink_old_gala_aankondiging', '2016'
	-- gala reminders (kept live)
	UNION ALL SELECT 16991, 'ink_old_gala_herinnering', '2017'
	UNION ALL SELECT 7455,  'ink_old_gala_herinnering', '2016'
) AS t
JOIN wpjj_posts p ON p.ID = t.post_id AND p.post_type = 'page'
WHERE NOT EXISTS (
	SELECT 1 FROM wpjj_postmeta m
	WHERE m.post_id = t.post_id AND m.meta_key = t.meta_key
);

-- Check: should return 36 rows (10 results + 10 photos + 9 competition announcements
-- + 1 closure + 4 gala announcements + 2 gala reminders).
SELECT post_id, meta_key, meta_value FROM wpjj_postmeta
WHERE meta_key IN (
	'ink_old_uitslae', 'ink_old_gala_fotos',
	'ink_old_kompetisie_aankondiging', 'ink_old_gala_aankondiging',
	'ink_old_kompetisie_sluiting', 'ink_old_gala_herinnering'
)
ORDER BY meta_key, meta_value;
