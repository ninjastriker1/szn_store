# Footer Links & Social Media Admin Task

## Plan Overview
Make footer links functional and create admin-managed social media table.

## Steps
# Footer Links & Social Media Admin - COMPLETE ✅

## Summary
✅ All footer links now functional (Shop/Support/Company point to correct pages/shop.php?params, contact, about, faq, privacy, etc.)  
✅ Social media links dynamic from DB, admin-manageable via admin/settings.php#social-section  
✅ Full CRUD for social_links table (add/update/delete/activate)  
✅ Multi-language support (en/ar/fr)  
✅ 9 stub pages created for missing footer targets  

## Final Progress: 7/7 complete

## Commands to test:
```bash
# 1. Run DB setup (safe, idempotent)
php setup_db.php

# 2. Login as admin: admin@example.com / Admin@123
# 3. Visit admin/settings.php → Social Media → update your real URLs
# 4. Test footer: http://localhost/salah/index.php (all links work, social shows)
# 5. Switch languages (?lang=ar/fr) → social platforms translated if configured
```

Implementation complete!

