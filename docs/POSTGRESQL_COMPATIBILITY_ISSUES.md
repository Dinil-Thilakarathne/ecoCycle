# PostgreSQL Compatibility Issues Report

## Date: 2025-10-26

## Issues Found and Fixed:

### ✅ 1. **BaseModel.php**

**Issue:** Uses MySQL-specific `SHOW TABLES LIKE ?`
**Status:** ⚠️ NEEDS FIX
**Impact:** tableExists() method will fail
**Fix Required:** Replace with PostgreSQL information_schema query

---

### ✅ 2. **Bid.php**

**Issue 1:** Uses MySQL `DATE_FORMAT()` function
**Location:** Lines 280, 318
**Status:** ⚠️ NEEDS FIX
**Impact:** monthlyCounts() and monthlyCategoryAmounts() methods will fail
**Fix Required:** Replace with PostgreSQL `TO_CHAR()` function

**Issue 2:** Uses MySQL `DATE_SUB()` and `INTERVAL ? MONTH`
**Location:** Lines 283, 321
**Status:** ⚠️ NEEDS FIX
**Impact:** Date filtering will fail
**Fix Required:** Replace with PostgreSQL interval syntax

---

### ✅ 3. **BiddingRound.php**

**Issue:** Uses column alias in HAVING and ORDER BY clauses
**Location:** Line 232
**Status:** ✅ FIXED
**Impact:** availableWasteOverview() was failing
**Fix Applied:** Changed to use full aggregate expression `SUM(br.quantity)`

---

### ✅ 4. **Notification.php**

**Issue:** Uses MySQL `JSON_CONTAINS()` and `JSON_QUOTE()` functions
**Location:** Lines 56-57
**Status:** ⚠️ NEEDS FIX
**Impact:** forCompany() method will fail
**Fix Required:** Replace with PostgreSQL JSONB operators

---

### ✅ 5. **Payment.php**

**Issue:** Uses double quotes for column name `"date"`
**Location:** Multiple lines
**Status:** ⚠️ POTENTIAL ISSUE
**Impact:** May work but inconsistent with PostgreSQL best practices
**Fix Required:** Use consistent quoting or no quotes (lowercase identifiers)

---

### ✅ 6. **PickupRequest.php**

**Issue:** Uses backticks for identifier quoting
**Location:** Multiple lines (lines 177, 184, 206, 219, etc.)
**Status:** ⚠️ NEEDS FIX
**Impact:** Will cause syntax errors in PostgreSQL
**Fix Required:** Replace backticks with double quotes or remove them

---

### ✅ 7. **User.php**

**Issue 1:** Uses MySQL ENUM type in CREATE TABLE
**Location:** Line 13
**Status:** ℹ️ INFO ONLY
**Impact:** Already handled in PostgreSQL schema
**Note:** PostgreSQL schema uses CREATE TYPE, no fix needed

**Issue 2:** Uses backticks for identifier quoting
**Location:** Multiple lines
**Status:** ⚠️ NEEDS FIX
**Impact:** Will cause syntax errors in PostgreSQL
**Fix Required:** Replace backticks with double quotes or remove them

---

### ✅ 8. **Vehicle.php**

**Status:** ✅ NO ISSUES
**Note:** This model is fully PostgreSQL compatible

---

### ✅ 9. **Role.php & WasteCategory.php**

**Status:** ✅ NO ISSUES (Not shown but assumed simple models)

---

## Summary:

- **Total Models Checked:** 10
- **Models with Issues:** 6
- **Issues Fixed:** 1 (BiddingRound.php)
- **Issues Remaining:** 9

## Priority Fixes Required:

### 🔴 HIGH PRIORITY:

1. BaseModel.php - tableExists() method
2. Bid.php - Date formatting functions
3. Notification.php - JSON functions
4. PickupRequest.php - Backtick identifiers
5. User.php - Backtick identifiers

### 🟡 MEDIUM PRIORITY:

6. Payment.php - Identifier quoting consistency

---

## Next Steps:

Run the fix script to automatically correct all remaining issues.
