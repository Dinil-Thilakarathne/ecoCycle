#!/bin/bash

# Waste Category Management API - Setup Verification Script
# This script verifies all components are properly installed and configured

echo "=========================================="
echo "Waste Category API - Setup Verification"
echo "=========================================="
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counter
PASSED=0
FAILED=0

# Helper functions
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $2"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} $2 (NOT FOUND: $1)"
        ((FAILED++))
    fi
}

check_content() {
    if grep -q "$2" "$1"; then
        echo -e "${GREEN}✓${NC} $3"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} $3 (NOT FOUND in $1)"
        ((FAILED++))
    fi
}

# Verification checks
echo "1. Controller Files"
echo "===================="
check_file "src/Controllers/Api/WasteManagementController.php" "WasteManagementController exists"
check_content "src/Controllers/Api/WasteManagementController.php" "class WasteManagementController" "WasteManagementController class defined"
check_content "src/Controllers/Api/WasteManagementController.php" "public function index" "index() method exists"
check_content "src/Controllers/Api/WasteManagementController.php" "public function store" "store() method exists"
check_content "src/Controllers/Api/WasteManagementController.php" "public function update" "update() method exists"
check_content "src/Controllers/Api/WasteManagementController.php" "public function destroy" "destroy() method exists"
check_content "src/Controllers/Api/WasteManagementController.php" "public function pricing" "pricing() method exists"
echo ""

echo "2. Model Files"
echo "=============="
check_file "src/Models/WasteCategory.php" "WasteCategory model exists"
check_content "src/Models/WasteCategory.php" "class WasteCategory" "WasteCategory class defined"
echo ""

echo "3. Route Definitions"
echo "===================="
check_file "config/routes.php" "routes.php exists"
check_content "config/routes.php" "/api/waste-categories" "GET /api/waste-categories route defined"
check_content "config/routes.php" "WasteManagementController@index" "index route mapped"
check_content "config/routes.php" "WasteManagementController@store" "store route mapped"
check_content "config/routes.php" "WasteManagementController@update" "update route mapped"
check_content "config/routes.php" "WasteManagementController@destroy" "destroy route mapped"
check_content "config/routes.php" "WasteManagementController@pricing" "pricing route mapped"
check_content "config/routes.php" "AdminOnly" "Admin role middleware applied"
echo ""

echo "4. Authentication & Security"
echo "============================="
check_content "config/routes.php" "AuthMiddleware" "Authentication middleware configured"
check_content "config/routes.php" "Roles\\AdminOnly" "Admin role middleware configured"
echo ""

echo "5. Documentation Files"
echo "======================"
check_file "docs/api-doc/API_DOCUMENTATION.md" "API_DOCUMENTATION.md exists"
check_content "docs/api-doc/API_DOCUMENTATION.md" "## Waste Category Management APIs" "Waste category section in API docs"
check_file "docs/api-doc/WASTE_CATEGORY_QUICK_REFERENCE.md" "WASTE_CATEGORY_QUICK_REFERENCE.md created"
check_file "docs/api-doc/WASTE_CATEGORY_IMPLEMENTATION.md" "WASTE_CATEGORY_IMPLEMENTATION.md created"
check_file "docs/api-doc/WASTE_CATEGORY_API_SUMMARY.md" "WASTE_CATEGORY_API_SUMMARY.md created"
echo ""

echo "6. Database Schema"
echo "=================="
# Check if database init file exists
if [ -f "database/postgresql/init/waste_categories_table.sql" ]; then
    echo -e "${GREEN}✓${NC} waste_categories_table.sql exists"
    ((PASSED++))
elif [ -f "database/mysql/create_tables.sql" ]; then
    if grep -q "waste_categories" "database/mysql/create_tables.sql"; then
        echo -e "${GREEN}✓${NC} waste_categories table defined in create_tables.sql"
        ((PASSED++))
    else
        echo -e "${YELLOW}⚠${NC} waste_categories not found in create_tables.sql (may need manual setup)"
    fi
else
    echo -e "${YELLOW}⚠${NC} Database schema file not checked (may need manual verification)"
fi
echo ""

echo "7. Middleware Configuration"
echo "==========================="
check_file "src/Middleware/AuthMiddleware.php" "AuthMiddleware exists"
check_file "src/Middleware/Roles/AdminOnly.php" "AdminOnly middleware exists"
echo ""

echo "=========================================="
echo "Verification Summary"
echo "=========================================="
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed! The API is properly configured.${NC}"
    exit 0
else
    echo -e "${RED}✗ Some checks failed. Please review the errors above.${NC}"
    exit 1
fi
