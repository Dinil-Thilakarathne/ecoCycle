# 🚀 Postman Quick Start Card

**Print this page for quick reference!**

---

## ⚡ 5-Minute Setup

### Step 1: Import Collection

```
Postman → Import → Select File
/Applications/XAMPP/xamppfiles/htdocs/ecoCycle/postman_collection.json
```

### Step 2: Create Environment

```
Environments → + Create
Name: ecoCycle - Local
Variable: base_url = http://localhost
Save (⌘+S)
Select from dropdown (top-right)
```

### Step 3: Test

```
Authentication → Login - Admin → Send
✅ Expected: 200 OK, session saved
```

---

## 🔑 Test Credentials

| Role          | Email                  | Password     |
| ------------- | ---------------------- | ------------ |
| **Admin**     | admin@ecocycle.com     | admin123     |
| **Customer**  | customer@ecocycle.com  | customer123  |
| **Company**   | company@ecocycle.com   | company123   |
| **Collector** | collector@ecocycle.com | collector123 |

---

## ⌨️ Essential Shortcuts

| Action  | Mac       | Windows        |
| ------- | --------- | -------------- |
| Send    | ⌘ + Enter | Ctrl + Enter   |
| Save    | ⌘ + S     | Ctrl + S       |
| Console | ⌘ + ⌥ + C | Ctrl + Alt + C |
| Format  | ⌘ + B     | Ctrl + B       |

---

## 🎯 Quick Workflows

### Vehicle CRUD (2 min)

1. Login - Admin
2. List All Vehicles
3. Create Vehicle
4. Get Vehicle Details
5. Update Vehicle
6. Delete Vehicle

### Pickup Process (3 min)

1. Login - Customer → Create Pickup
2. Login - Admin → Assign Collector
3. Login - Collector → Complete Pickup

### Bidding (3 min)

1. Login - Admin → Create Round
2. Login - Company → Place Bid
3. Login - Admin → Approve Round

---

## 🐛 Quick Fixes

| Error                 | Fix                       |
| --------------------- | ------------------------- |
| 401 Unauthorized      | Login first               |
| 403 Forbidden         | Wrong user role           |
| Connection Refused    | Start XAMPP               |
| Variables not working | Select environment        |
| Session lost          | Settings → Enable cookies |

---

## 📊 Collection Structure

```
29 Requests Total:
├── Authentication (5)
├── Admin - Vehicles (5)
├── Admin - Bidding (6)
├── Customer - Pickups (4)
├── Collector - Status (2)
├── Company - Bids (3)
└── Development & Debug (4)
```

---

## 🎓 Documentation Quick Links

| Need     | Document                                                       |
| -------- | -------------------------------------------------------------- |
| Overview | [POSTMAN_SUMMARY.md](./POSTMAN_SUMMARY.md)                     |
| Details  | [POSTMAN_TESTING_GUIDE.md](./POSTMAN_TESTING_GUIDE.md)         |
| Setup    | [POSTMAN_SETUP_CHECKLIST.md](./POSTMAN_SETUP_CHECKLIST.md)     |
| Visuals  | [POSTMAN_WORKFLOW_DIAGRAMS.md](./POSTMAN_WORKFLOW_DIAGRAMS.md) |
| API Ref  | [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)                 |

---

## 💡 Pro Tips

✅ **Tip 1:** Use Collection Runner for automated testing
✅ **Tip 2:** Create environments for Local/Staging/Production
✅ **Tip 3:** Check Console (⌘⌥C) for debugging
✅ **Tip 4:** Variables auto-populate after create operations
✅ **Tip 5:** Session persists - login once per session

---

## ✅ Verification Checklist

Before starting development:

- [ ] Collection imported
- [ ] Environment created & selected
- [ ] Settings configured (cookies enabled)
- [ ] XAMPP running
- [ ] Database seeded
- [ ] Login test passed (200 OK)
- [ ] Variables auto-populating
- [ ] Session persisting

---

## 🆘 Need Help?

**Console:** ⌘⌥C / Ctrl+Alt+C
**Logs:** `storage/logs/`
**Docs:** `/docs` folder
**Issues:** [GitHub Issues](https://github.com/Dinil-Thilakarathne/ecoCycle/issues)

---

## 📈 Success Metrics

After setup, expect:

- ✅ **Pass Rate:** 100%
- ⏱️ **Response Time:** < 100ms avg
- ⚡ **Test Run:** < 5 minutes
- 🎯 **Coverage:** All endpoints

---

## 🎉 You're Ready!

**Setup Time:** 5 minutes
**Total Requests:** 29 endpoints
**Documentation:** 4 guides
**Status:** 🟢 Production Ready

**Start Testing:** 🚀

1. Import collection
2. Create environment
3. Login as Admin
4. Test your first API!

---

**Print Date:** ******\_******
**Team Member:** ******\_******
**Setup Complete:** ☐

---

**🎓 For full documentation, see:**
`/Applications/XAMPP/xamppfiles/htdocs/ecoCycle/docs/`

**Happy Testing! 🚀**
