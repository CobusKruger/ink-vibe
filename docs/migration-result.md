# Result of migration

```
cobus@MacBook-Pro public % wp ink migrate-sanitise
Success: Databasis gesuiwer: 0 tydelike inskrywings, 0 voltooide take, 0 wees-logboekinskrywings verwyder (oorgeslaan — reeds gedoen).
cobus@MacBook-Pro public % wp ink migrate-users
Success: Gebruikers herklassifiseer: 0 na basisrol, 0 personeel behou, 0 profielvelde opgeruim (oorgeslaan — reeds gedoen).
cobus@MacBook-Pro public % wp ink migrate-tiers wp-content/uploads/private/tier-import.csv
Success: Graderings ingevoer: 0 gestel, 0 na brons verstek + gemerk, 0 sonder rekening.
cobus@MacBook-Pro public % wp ink verify-subscriptions
  • active: 37
  • expired: 41
Success: Lidmaatskappe geverifieer: 78 totaal, 36 met vervaldatum, 1 onbeperk, 0 gemerk vir aandag.
cobus@MacBook-Pro public % wp ink migrate-library-training
Success: Biblioteek/Opleiding gemigreer: 0 biblioteek-items, 0 opleiding-artikels, 0 terme toegeken.
cobus@MacBook-Pro public % wp ink migrate-posts
Warning: Slug-botsing: argief "biblioteek" oorvleuel met 'n bestaande bladsy.
Warning: Slug-botsing: argief "opleiding" oorvleuel met 'n bestaande bladsy.
Warning: Slug-botsing: argief "inkpols" oorvleuel met 'n bestaande bladsy.
Success: Plasings herklassifiseer: 11030 na tipe-CPT, 3358 na skryfwerk, 0 hernoem, 0 oorgeslaan.
cobus@MacBook-Pro public % wp ink migrate-challenges
Success: Uitdagings gemigreer: 0 geskep, 0 stukke gekoppel.
cobus@MacBook-Pro public % wp ink migrate-inkpols 
Success: InkPols-uitgawes gemigreer: 0 geskep, 0 herversoen, 0 PDF's herkoppel.
cobus@MacBook-Pro public % wp ink clean-shortcodes
Success: WPBakery-kortkodes opgeruim: 17 plasings skoongemaak.
cobus@MacBook-Pro public % wp ink rebuild-navigation
Success: Navigasie geskep: 9 items.
cobus@MacBook-Pro public % wp ink migrate-follows
Success: Vriendskappe → volg: 5758 volg-rekords geskep, 2077 hangend oorgeslaan, 0 wees-rande oorgeslaan, 12004 aktiwiteite gesnoei.
cobus@MacBook-Pro public % wp ink migrate-options
Success: Opsies oorgedra: 1 waardes, ligging "af".
cobus@MacBook-Pro public % wp ink generate-redirects
Success: Aanstuurings (301) gegenereer: 14322 reëls.
cobus@MacBook-Pro public % wp ink verify-redirects --fix
Aanstuurkaart: 14322 reëls — kettings: 0, lusse: 0, leë teikens: 0.
Success: Kettings platgemaak — aanstuurkaart is nou heel.
cobus@MacBook-Pro public % wp ink verify-media
```