# 🔒 Security Release v1.29.1 - Read Me First

**Status:** ✅ Security fixes implemented  
**Release Date:** 2026-03-14  
**Severity:** 🔴 High - Update Recommended

---

## 🚨 Quick Summary

This security release fixes **2 critical vulnerabilities** in the WooCommerce Cielo Payment Gateway:

1. **REST API Endpoints** - Public endpoints without authentication (SEC-001)
2. **Refund Filters** - Filters without permission checks (SEC-002)

---

## 📚 Documentation

### For All Users

👉 **Start here:** [CHANGELOG.md](./CHANGELOG.md)
- Quick overview of changes
- What's fixed in v1.29.1
- Breaking changes summary

### For Developers

👉 **Integration guide:** [MIGRATION_GUIDE_v1.29.1.md](./MIGRATION_GUIDE_v1.29.1.md)
- REST API changes and how to adapt
- Refund filter changes
- Code examples (JavaScript, PHP, cURL)
- Common problems and solutions
- Testing checklist

### For Security Teams

👉 **Technical details:** [SECURITY_FIXES.md](./SECURITY_FIXES.md)
- Detailed vulnerability analysis
- Risk assessment
- Implementation details
- Test scenarios
- Audit trail information

---

## ⚡ Quick Actions

### I'm an End User

**Action:** Update to v1.29.1 as soon as possible

**Impact:** None - Everything continues working normally

**Why update:** Prevents unauthorized access to sensitive operations

### I'm a Developer Using the REST API

**Action:** Review [MIGRATION_GUIDE_v1.29.1.md](./MIGRATION_GUIDE_v1.29.1.md) Section "REST API"

**Impact:** ✅ Minimal - WordPress automatically sends nonce in REST requests

**What to check:**
- Frontend checkout still works (automatic nonce)
- External scripts may need nonce header added

**Example:**
```javascript
// Most cases work automatically:
fetch('/wp-json/lknWCGatewayCielo/checkCard?cardbin=411111', {
    credentials: 'include' // Important!
})
```

### I'm Using Refund Filters

**Action:** Review [MIGRATION_GUIDE_v1.29.1.md](./MIGRATION_GUIDE_v1.29.1.md) Section "Filtros de Refund"

**Impact:** ✅ None for most cases - Admin users already have permissions

**What to check:**
- If using filters in cronjobs/webhooks, see migration guide
- Filters now log usage for audit (when debug enabled)

**Your filter continues working if:**
- ✅ Executed by user with `manage_woocommerce` capability
- ✅ Used in WooCommerce admin context (normal case)

---

## 🔍 What Changed?

### REST API Endpoints

| Endpoint | Before v1.29.1 | After v1.29.1 |
|----------|----------------|---------------|
| `/checkCard` | ❌ Public | ✅ Requires nonce |
| `/clearOrderLogs` | ❌ Public | ✅ Requires admin |
| `/getAcessToken` | ❌ Public | ✅ Requires nonce |
| `/getCardBrand` | ❌ Public | ✅ Requires nonce |

### Refund Filters

| Filter | Before v1.29.1 | After v1.29.1 |
|--------|----------------|---------------|
| `lkn_wc_cielo_credit_refund` | ❌ No checks | ✅ Permission required |
| `lkn_wc_cielo_debit_refund` | ❌ No checks | ✅ Permission required |
| `lkn_wc_cielo_google_pay_refund` | ❌ No checks | ✅ Permission required |

---

## ✅ Compatibility

### Backward Compatible

✅ **Your site will continue working normally**
- Checkout forms work (automatic nonce)
- Refunds work for admin users
- Filters work for admin users
- No database changes
- No configuration changes needed

### May Need Attention

⚠️ **These scenarios may need updates:**
1. External scripts calling REST API → Add nonce
2. Cronjobs using refund filters → Elevate permissions
3. Custom integrations → Review migration guide

---

## 🧪 Testing

### Quick Test Checklist

- [ ] Checkout with credit card works
- [ ] Checkout with debit card works
- [ ] Refund as admin works
- [ ] clearOrderLogs as admin works

**Expected results:** Everything should work normally

---

## 🐛 Having Issues?

### Error: "Invalid security token" (403)

**Cause:** Nonce not being sent

**Solution:**
```javascript
// Ensure credentials: 'include' is present
fetch('/wp-json/lknWCGatewayCielo/checkCard?cardbin=411111', {
    credentials: 'include' // This is essential!
})
```

### Error: "You do not have permission" (403)

**Cause:** User doesn't have required permissions

**Solution:**
- For endpoints: Ensure user is logged in
- For clearOrderLogs: Ensure user has `manage_woocommerce`
- For refunds: Ensure user has `manage_woocommerce`

### More Help

- **Migration Guide:** [MIGRATION_GUIDE_v1.29.1.md](./MIGRATION_GUIDE_v1.29.1.md)
- **Technical Docs:** [SECURITY_FIXES.md](./SECURITY_FIXES.md)
- **GitHub Issues:** https://github.com/LinkNacional/lkn-wc-gateway-cielo/issues
- **Email:** contato@linknacional.com.br

---

## 🔐 Security Disclosure

Found a security issue? **Don't open a public issue!**

- Email: security@linknacional.com.br
- Response time: 48 hours
- We practice responsible disclosure

---

## 📈 Statistics

| Metric | Value |
|--------|-------|
| Vulnerabilities Fixed | 2 (High severity) |
| Endpoints Protected | 4 |
| Gateways Protected | 3 |
| Code Changes | ~250 lines |
| Documentation | 815 lines |
| Test Scenarios | 25+ |

---

## 🎯 Next Steps

1. ✅ **Update immediately** to v1.29.1
2. 📖 **Read** [MIGRATION_GUIDE_v1.29.1.md](./MIGRATION_GUIDE_v1.29.1.md) if you integrate with the API
3. 🧪 **Test** your checkout and refund processes
4. 📊 **Monitor** logs for any issues
5. 📝 **Report** any problems via GitHub issues

---

**Version:** 1.29.1  
**Release Date:** 2026-03-14  
**Status:** ✅ Production Ready

**Security First. Compatibility Always.**
