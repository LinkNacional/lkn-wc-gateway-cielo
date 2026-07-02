# 🚀 Deployment Instructions - Security Release v1.29.1

**Target Version:** 1.29.1  
**Release Type:** Security Release (Critical)  
**Priority:** 🔴 High - Deploy ASAP  
**Date:** 2026-03-14

---

## 📋 Pre-Deployment Checklist

### Prerequisites

- [ ] All code changes merged to branch: `copilot/mapear-nivel-acoplamento-pagamento`
- [ ] All commits pushed to remote repository
- [ ] Documentation complete and reviewed
- [ ] Local tests passed
- [ ] Code review completed (if applicable)

### Documentation Verification

- [ ] SECURITY_RELEASE_README.md - Entry point exists
- [ ] SECURITY_FIXES.md - Technical documentation complete
- [ ] MIGRATION_GUIDE_v1.29.1.md - Migration guide ready
- [ ] SECURITY_SUMMARY.txt - Visual summary available
- [ ] CHANGELOG.md - Updated with v1.29.1 entry

---

## 🧪 Staging Deployment

### Step 1: Backup

```bash
# Backup production database
wp db export backup-before-v1.29.1-$(date +%Y%m%d-%H%M%S).sql

# Backup plugin files
cp -r wp-content/plugins/lkn-wc-gateway-cielo \
   backups/lkn-wc-gateway-cielo-backup-$(date +%Y%m%d)
```

### Step 2: Deploy to Staging

```bash
# Navigate to staging site
cd /path/to/staging/wp-content/plugins/lkn-wc-gateway-cielo

# Pull latest changes
git fetch origin
git checkout copilot/mapear-nivel-acoplamento-pagamento
git pull origin copilot/mapear-nivel-acoplamento-pagamento

# Verify version
grep "Version:" lkn-wc-gateway-cielo.php
# Should show: Version: 1.29.1
```

### Step 3: Verify Files Changed

```bash
# Check modified files
git diff origin/main..HEAD --name-only

# Expected files:
# includes/LknWCGatewayCieloEndpoint.php
# includes/LknWCGatewayCieloCredit.php
# includes/LknWCGatewayCieloDebit.php
# includes/LknWCGatewayCieloGooglePay.php
# CHANGELOG.md
# SECURITY_RELEASE_README.md
# SECURITY_FIXES.md
# MIGRATION_GUIDE_v1.29.1.md
# SECURITY_SUMMARY.txt
```

### Step 4: Clear Caches

```bash
# Clear WordPress object cache
wp cache flush

# Clear WooCommerce cache
wp wc tool run clear_transients

# Clear any page cache (if applicable)
# Examples:
# wp cache flush (if using Redis/Memcached)
# wp w3-total-cache flush all (if using W3TC)
# wp rocket clean --confirm (if using WP Rocket)
```

---

## ✅ Testing on Staging

### Manual Tests

#### A. Checkout Tests

**Test 1: Credit Card Checkout**
1. Navigate to shop page
2. Add product to cart
3. Proceed to checkout
4. Select "Cielo - Credit Card" payment
5. Fill card details (use test card: 4111 1111 1111 1111)
6. Complete checkout
7. **Expected:** Order completes successfully
8. **Verify:** No console errors, no 403 errors

**Test 2: Debit Card Checkout**
1. Select "Cielo - Debit Card" payment
2. Fill card details
3. Complete checkout
4. **Expected:** Order completes successfully
5. **Verify:** getAcessToken endpoint works with nonce

**Test 3: PIX Checkout**
1. Select "Cielo PIX" payment
2. Complete checkout
3. **Expected:** QR code displays
4. **Verify:** PIX generation works

#### B. REST API Tests

**Test 4: checkCard Endpoint (No Auth)**
```bash
# Should return 403
curl -X GET "https://staging.site/wp-json/lknWCGatewayCielo/checkCard?cardbin=411111"

# Expected response:
# {
#   "code": "rest_forbidden",
#   "message": "Invalid security token.",
#   "data": { "status": 403 }
# }
```

**Test 5: checkCard Endpoint (With Auth)**
```bash
# Login as customer first, then:
curl -X GET "https://staging.site/wp-json/lknWCGatewayCielo/checkCard?cardbin=411111" \
  -H "X-WP-Nonce: {valid_nonce}" \
  --cookie "wordpress_logged_in_{hash}={cookie_value}"

# Expected: 200 OK with card data
```

**Test 6: clearOrderLogs (Non-Admin)**
```bash
# As customer/editor (non-admin):
curl -X DELETE "https://staging.site/wp-json/lknWCGatewayCielo/clearOrderLogs" \
  -H "X-WP-Nonce: {valid_nonce}"

# Expected: 403 Forbidden
```

**Test 7: clearOrderLogs (Admin)**
```bash
# As admin:
curl -X DELETE "https://staging.site/wp-json/lknWCGatewayCielo/clearOrderLogs" \
  -H "X-WP-Nonce: {admin_nonce}" \
  --cookie "wordpress_logged_in_{hash}={admin_cookie}"

# Expected: 200 OK with success message
```

#### C. Refund Tests

**Test 8: Refund as Admin**
1. Login as admin
2. Navigate to WooCommerce → Orders
3. Open a completed order
4. Click "Refund" button
5. Enter amount and reason
6. Process refund
7. **Expected:** Refund processes successfully
8. **Verify:** Check logs in wp-content/uploads/wc-logs/

**Test 9: Refund as Editor (Should Fail)**
1. Login as editor (without manage_woocommerce)
2. Try to access refund functionality
3. **Expected:** Should not be able to refund
4. **Verify:** No refund processed

#### D. Security Logs Tests

**Test 10: Check Audit Logs**
```bash
# Check security logs
tail -50 wp-content/uploads/wc-logs/woocommerce-cielo-security-*.log

# Should show:
# - clearOrderLogs operations (if executed)
# - User IDs
# - Timestamps
```

**Test 11: Check Refund Logs**
```bash
# Check refund security logs
tail -50 wp-content/uploads/wc-logs/woocommerce-cielo-credit-security-*.log

# Should show (if refund attempted):
# - Refund attempts
# - Permission checks
# - Filter usage
```

### Automated Tests (If Available)

```bash
# Run PHPUnit tests
composer test

# Run specific security tests
composer test tests/security/

# Run integration tests
composer test:integration
```

---

## 📊 Monitoring on Staging

### Metrics to Monitor

**1. Error Logs**
```bash
# Watch error log in real-time
tail -f wp-content/debug.log

# Check for:
# - Permission denied errors
# - Nonce verification failures
# - Any PHP errors
```

**2. WooCommerce Logs**
```bash
# Monitor WooCommerce logs
tail -f wp-content/uploads/wc-logs/woocommerce-cielo-*.log

# Check for:
# - Security log entries
# - Audit trail entries
# - No unexpected errors
```

**3. Server Logs**
```bash
# Check Apache/Nginx error logs
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/nginx/error.log
```

---

## ✅ Staging Sign-Off

### Approval Checklist

- [ ] All manual tests passed
- [ ] No console errors in browser
- [ ] No PHP errors in logs
- [ ] Checkout works for all payment methods
- [ ] Refunds work for admin users
- [ ] REST API endpoints properly protected
- [ ] Audit logs are being created
- [ ] No performance degradation observed
- [ ] Documentation accessible and correct

### Sign-Off

**Tested by:** ___________________  
**Date:** ___________________  
**Approved for Production:** [ ] Yes [ ] No  
**Notes:** ___________________

---

## 🚀 Production Deployment

### Pre-Production Steps

1. **Schedule Maintenance Window**
   - Duration: 30 minutes
   - Time: Low traffic period
   - Notify users (if applicable)

2. **Prepare Rollback Plan**
   - Have backup ready
   - Test rollback procedure
   - Document rollback steps

### Production Deployment Steps

#### Step 1: Enable Maintenance Mode

```bash
# Enable maintenance mode
wp maintenance-mode activate

# Or create .maintenance file manually
echo '<?php $upgrading = time(); ?>' > .maintenance
```

#### Step 2: Backup Production

```bash
# Full database backup
wp db export production-backup-v1.29.1-$(date +%Y%m%d-%H%M%S).sql

# Backup plugin files
tar -czf wc-cielo-backup-$(date +%Y%m%d).tar.gz \
  wp-content/plugins/lkn-wc-gateway-cielo/

# Verify backup
ls -lh *backup*
```

#### Step 3: Deploy Code

```bash
# Navigate to production plugin directory
cd /path/to/production/wp-content/plugins/lkn-wc-gateway-cielo

# Pull latest changes
git fetch origin
git checkout copilot/mapear-nivel-acoplamento-pagamento
git pull origin copilot/mapear-nivel-acoplamento-pagamento

# Verify version
grep "Version:" lkn-wc-gateway-cielo.php
# Should show: Version: 1.29.1
```

#### Step 4: Clear Caches

```bash
# WordPress cache
wp cache flush

# WooCommerce transients
wp wc tool run clear_transients

# Object cache (if using)
wp redis flush  # or memcached flush

# Page cache
# (Run your cache plugin's flush command)
```

#### Step 5: Verify Files

```bash
# Check file permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Verify file integrity
ls -la includes/LknWCGatewayCieloEndpoint.php
ls -la includes/LknWCGatewayCieloCredit.php
ls -la includes/LknWCGatewayCieloDebit.php
ls -la includes/LknWCGatewayCieloGooglePay.php
```

#### Step 6: Smoke Tests

```bash
# Quick smoke tests
# Test 1: Homepage loads
curl -I https://production.site/

# Test 2: REST API responds
curl https://production.site/wp-json/

# Test 3: Plugin is active
wp plugin list | grep lkn-wc-gateway-cielo
```

#### Step 7: Disable Maintenance Mode

```bash
# Disable maintenance mode
wp maintenance-mode deactivate

# Or remove .maintenance file
rm .maintenance
```

---

## 📈 Post-Deployment Monitoring

### First 30 Minutes

**Monitor continuously:**

1. **Error Logs**
   ```bash
   tail -f wp-content/debug.log
   tail -f /var/log/apache2/error.log
   ```

2. **WooCommerce Logs**
   ```bash
   tail -f wp-content/uploads/wc-logs/woocommerce-cielo-*.log
   ```

3. **Server Metrics**
   - CPU usage
   - Memory usage
   - Response times

### First 2 Hours

**Check metrics:**
- [ ] Order completion rate (should be normal)
- [ ] Error rate (should be low/zero)
- [ ] Failed payment rate (should be normal)
- [ ] Support tickets (should be low/none)

### First 24 Hours

**Review:**
- [ ] Security logs for unusual activity
- [ ] Order processing stats
- [ ] Customer feedback
- [ ] Support tickets related to checkout

### First Week

**Analyze:**
- [ ] Overall order volume (compare to previous week)
- [ ] Payment success rate
- [ ] Refund processing
- [ ] No security incidents reported

---

## 🔄 Rollback Procedure

### If Issues Are Detected

**Step 1: Quick Assessment**
- Severity: Critical? Major? Minor?
- Affected: All users? Specific payment method?
- Impact: Orders failing? Security issue? Performance?

**Step 2: Decide on Rollback**

**Rollback if:**
- Orders are failing consistently
- Security vulnerability still exposed
- Critical functionality broken
- User data at risk

**Don't rollback if:**
- Minor UI issues
- Single isolated error
- Can be fixed with quick patch
- Non-critical feature affected

**Step 3: Execute Rollback**

```bash
# Enable maintenance mode
wp maintenance-mode activate

# Restore from backup
cd /path/to/production/wp-content/plugins/
rm -rf lkn-wc-gateway-cielo/
tar -xzf wc-cielo-backup-YYYYMMDD.tar.gz

# Or revert to previous commit
cd lkn-wc-gateway-cielo/
git checkout main  # or previous stable branch
git pull origin main

# Clear caches
wp cache flush
wp wc tool run clear_transients

# Disable maintenance mode
wp maintenance-mode deactivate
```

**Step 4: Post-Rollback**
- Verify site is working
- Notify stakeholders
- Document the issue
- Plan fix and redeployment

---

## 📞 Emergency Contacts

### During Deployment

**Technical Lead:** ___________________  
**DevOps:** ___________________  
**Security Team:** ___________________  
**Support Team:** ___________________  

### Emergency Procedures

**If critical issue during deployment:**
1. Stop deployment immediately
2. Contact technical lead
3. Assess severity
4. Execute rollback if necessary
5. Document incident
6. Schedule post-mortem

---

## 📝 Post-Deployment Report

### Deployment Summary

**Date:** ___________________  
**Time:** ___________________  
**Duration:** ___________________  
**Deployed by:** ___________________  

### Checklist

- [ ] Code deployed successfully
- [ ] All tests passed
- [ ] No errors in logs
- [ ] Monitoring active
- [ ] Documentation published
- [ ] Stakeholders notified

### Issues Encountered

| Issue | Severity | Resolution | Time |
|-------|----------|------------|------|
|       |          |            |      |
|       |          |            |      |

### Metrics

| Metric | Before | After | Change |
|--------|--------|-------|--------|
| Order completion rate | ___% | ___% | ___% |
| Error rate | ___% | ___% | ___% |
| Response time | ___ms | ___ms | ___ms |

### Notes

___________________
___________________
___________________

---

## ✅ Sign-Off

**Deployment Approved:** [ ] Yes [ ] No  
**Production Stable:** [ ] Yes [ ] No  
**Monitoring Active:** [ ] Yes [ ] No  
**Documentation Updated:** [ ] Yes [ ] No  

**Signed by:** ___________________  
**Date:** ___________________  
**Time:** ___________________  

---

**Version:** 1.29.1  
**Status:** Ready for Production  
**Priority:** 🔴 Critical Security Release

**Deploy with confidence. Monitor with vigilance.**
