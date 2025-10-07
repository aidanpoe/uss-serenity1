# USS-VOYAGER GDPR Compliance Report
## Data Protection and Privacy Analysis Under UK GDPR

**Report Date:** August 22, 2025  
**Website:** USS-VOYAGER LCARS Roleplay System  
**Jurisdiction:** United Kingdom GDPR  
**Report Type:** Comprehensive Data Usage Analysis  

---

## Executive Summary

The USS-VOYAGER website is a **Star Trek-themed roleplay platform** that collects and processes both **real user data** (for authentication and system operation) and **fictional roleplay data** (for in-character activities). This report analyzes data practices under UK GDPR requirements and provides recommendations for compliance.

**Key Finding:** The website processes legitimate user data for authentication and roleplay purposes, but requires **immediate attention** to GDPR compliance, particularly around consent, data retention policies, and user rights implementation.

---

## 1. Data Classification

### 1.1 Real User Data (Personal Data under GDPR)

#### **Authentication & Account Data**
- **Steam ID:** Unique identifier from Steam authentication (64-bit number)
- **Steam Profile Information:**
  - Steam username/persona name
  - Steam avatar/profile images
  - Steam account creation date
  - Steam community visibility settings
  - Steam real name (if provided by user)
  - Steam profile URL

#### **Technical & Session Data**
- **Session Management:**
  - PHP session cookies (HTTP-only, secure)
  - Session timeouts (1 hour)
  - CSRF tokens for security
- **Access Logs:**
  - Login timestamps (`last_login` field)
  - Last activity tracking (`last_active` timestamps)
  - IP addresses (stored in audit logs)
  - User agent strings (browser/device information)

#### **Account Management Data**
- **User Accounts Table:**
  - Internal user ID (auto-increment)
  - Username (chosen by user)
  - Department permissions (MED/SCI, ENG/OPS, SEC/TAC, Command)
  - Account status (active/inactive)
  - Account creation date
  - Password change requirements

### 1.2 Roleplay/Fictional Data (Not Personal Data)

#### **Character Roster Information**
- **Character Profiles:**
  - Character name (fictional)
  - Starfleet rank (fictional)
  - Species (fictional - Human, Vulcan, etc.)
  - Department assignment (fictional)
  - Position/role (fictional)
  - Character images/photos (uploaded by users)

#### **Roleplay Activities**
- **Medical Records:** Fictional medical conditions and treatments
- **Security Reports:** Fictional security incidents and investigations
- **Criminal Records:** Fictional criminal activities and punishments
- **Engineering Reports:** Fictional technical problems and repairs
- **Science Reports:** Fictional scientific research and discoveries
- **Training Records:** Fictional training completions and documents
- **Cargo Bay Inventory:** Fictional cargo and supply management

---

## 2. Legal Basis for Processing

### 2.1 Current Legal Basis
- **Legitimate Interest (Article 6(1)(f)):** Operating a hobby/entertainment website
- **Consent (Article 6(1)(a)):** User voluntarily creates accounts and participates

### 2.2 GDPR Compliance Issues
❌ **Missing explicit consent mechanisms**  
❌ **No clear privacy policy**  
❌ **No data retention policies**  
❌ **No user rights implementation**  

---

## 3. Data Collection Points

### 3.1 Steam Authentication (Primary)
**File:** `steamauth/steamauth.php`, `pages/steam_register.php`

**Data Collected:**
```php
// Steam API Response Data
$_SESSION['steam_steamid'] = $content['response']['players'][0]['steamid'];
$_SESSION['steam_personaname'] = $content['response']['players'][0]['personaname'];
$_SESSION['steam_avatar'] = $content['response']['players'][0]['avatar'];
$_SESSION['steam_realname'] = $content['response']['players'][0]['realname'];
```

**Process:**
1. User clicks "Sign in through Steam"
2. Redirected to Steam OpenID authentication
3. Steam returns profile data
4. User creates USS-VOYAGER account and character
5. Steam ID linked to local user account

### 3.2 Character Creation
**File:** `pages/steam_register.php`

**Data Collected:**
- Username (real choice by user)
- Character name, rank, species (fictional)
- Department selection (affects permissions)

### 3.3 Activity Logging
**Files:** Various department pages, training system, cargo bay

**Data Logged:**
- File access logs (training system)
- Audit trails (cargo bay operations)
- Login/logout timestamps
- Character activity tracking

---

## 4. Data Storage & Retention

### 4.1 Database Tables

#### **Real User Data Tables**
```sql
-- User accounts (real data)
users: id, username, steam_id, department, active, last_login, created_at

-- Session management (real data)  
sessions: PHP session files with timeout (1 hour)
```

#### **Roleplay Data Tables**
```sql
-- Character information (fictional)
roster: id, rank, first_name, last_name, species, department, position, image_path

-- Roleplay activities (fictional)
medical_records: patient conditions, treatments
criminal_records: incidents, investigations, punishments
security_reports: security incidents
engineering_reports: technical issues
science_reports: research projects
training_files: training documents and access logs
```

### 4.2 File Storage
- **Character Images:** `roster_images/` directory
- **Training Documents:** `training_files/` with department subdirectories
- **Deleted Files:** `training_files/deleted/` (90-day retention)

### 4.3 Current Retention Issues
❌ **No data retention policies defined**  
❌ **No automatic deletion procedures**  
❌ **Indefinite storage of user accounts**  
❌ **No cleanup of inactive accounts**  

---

## 5. Data Processing Activities

### 5.1 Authentication Processing
- **Purpose:** User login and session management
- **Data:** Steam ID, username, login timestamps
- **Retention:** Indefinite (until account deletion)
- **Access:** User, administrators

### 5.2 Roleplay System Processing
- **Purpose:** Character management and roleplay activities
- **Data:** Character profiles, fictional reports and records
- **Retention:** Indefinite (for roleplay continuity)
- **Access:** Department-based permissions

### 5.3 Audit Logging
- **Purpose:** Security and system monitoring
- **Data:** Access logs, IP addresses, user agents
- **Retention:** Indefinite
- **Access:** Administrators only

---

## 6. Data Sharing & Third Parties

### 6.1 Third-Party Services

#### **Steam API (Valve Corporation)**
- **Data Shared:** Steam ID for profile lookups
- **Purpose:** Authentication and profile information
- **Legal Basis:** Necessary for service operation
- **Location:** United States (Adequacy decision or SCCs required)

#### **No Other Third Parties**
✅ **No analytics services (Google Analytics, etc.)**  
✅ **No advertising networks**  
✅ **No social media integrations beyond Steam**  
✅ **No email services or newsletters**  

### 6.2 Data Transfers
- **Steam API calls:** UK → US (requires adequate safeguards)
- **No other international transfers identified**

---

## 7. Technical Security Measures

### 7.1 Implemented Security ✅
- **HTTPS enforcement** (production requirement)
- **Secure session cookies** (HttpOnly, Secure, SameSite)
- **CSRF protection** (on some forms)
- **Input sanitization** (on some systems)
- **Prepared SQL statements** (prevents SQL injection)
- **Password hashing** (though Steam auth is primary)
- **File upload restrictions** (type and size validation)

### 7.2 Security Gaps ❌
- **Missing CSRF protection** on criminal records and other forms
- **Inconsistent input sanitization**
- **No rate limiting on API endpoints**
- **Some debug files in production**

---

## 8. User Rights Under GDPR

### 8.1 Currently NOT Implemented ❌

#### **Right to Information (Articles 13-14)**
- No privacy policy or data processing notices
- No clear information about data collection

#### **Right of Access (Article 15)**
- No mechanism for users to request their data
- No user dashboard showing collected data

#### **Right to Rectification (Article 16)**
- Users can edit character profiles
- No mechanism to correct account data

#### **Right to Erasure (Article 17)**
- No account deletion functionality
- No data cleanup procedures

#### **Right to Restrict Processing (Article 18)**
- No mechanism to limit data processing

#### **Right to Data Portability (Article 20)**
- No data export functionality

#### **Right to Object (Article 21)**
- No opt-out mechanisms

---

## 9. Consent & Legal Notices

### 9.1 Missing Legal Framework ❌
- **No Privacy Policy**
- **No Terms of Service**
- **No Cookie Notice**
- **No Consent Management**
- **No Age Verification** (13+ requirement for Steam accounts)

### 9.2 Implied vs Explicit Consent
**Current Status:** Implied consent through registration  
**GDPR Requirement:** Explicit, informed consent  
**Gap:** Need clear consent mechanisms

---

## 10. Risk Assessment

### 10.1 Data Protection Risks

#### **HIGH RISK** 🔴
- **No user rights implementation:** Users cannot access, correct, or delete data
- **No data retention policies:** Indefinite storage without justification
- **Missing legal notices:** No privacy policy or consent framework

#### **MEDIUM RISK** 🟡
- **Steam API dependency:** Reliance on US-based service
- **Audit logging:** IP addresses stored without clear retention limits
- **Security gaps:** Some forms lack CSRF protection

#### **LOW RISK** 🟢
- **Roleplay data:** Mostly fictional content, limited privacy impact
- **No sensitive categories:** No health, political, or sexual orientation data
- **Limited data collection:** Only essential data for service operation

### 10.2 Compliance Risk Level
**Overall Risk:** **MEDIUM-HIGH** due to lack of user rights and legal framework

---

## 11. Recommendations for GDPR Compliance

### 11.1 IMMEDIATE ACTIONS (Within 30 Days)

#### **1. Create Privacy Policy**
- Document all data collection and processing
- Explain legal basis for processing
- Detail user rights and how to exercise them
- Include contact information for data protection queries

#### **2. Implement Data Subject Rights**
- Add account deletion functionality
- Create data export mechanism
- Allow users to view their collected data
- Provide data correction mechanisms

#### **3. Establish Data Retention Policies**
- Set retention periods for different data types
- Implement automatic cleanup procedures
- Define criteria for account deletion

#### **4. Add Consent Mechanisms**
- Clear consent during registration
- Opt-in for non-essential data collection
- Cookie consent banner (if needed)

### 11.2 MEDIUM-TERM ACTIONS (Within 90 Days)

#### **5. Enhance Security**
- Complete CSRF protection implementation
- Add rate limiting to prevent abuse
- Regular security audits
- Remove debug files from production

#### **6. Data Minimization Review**
- Assess necessity of all collected data
- Remove unnecessary logging
- Implement privacy by design

#### **7. Documentation & Training**
- Data protection impact assessments
- Staff training on GDPR compliance
- Incident response procedures

### 11.3 ONGOING COMPLIANCE

#### **8. Regular Reviews**
- Annual privacy policy reviews
- Data audit procedures
- User rights request handling
- Security monitoring

---

## 12. Sample Privacy Policy Framework

### 12.1 Required Sections
1. **Data Controller Information**
2. **Types of Data Collected**
3. **Legal Basis for Processing**
4. **Data Retention Periods**
5. **Your Rights Under GDPR**
6. **How to Exercise Rights**
7. **Data Security Measures**
8. **Third-Party Services (Steam)**
9. **Contact Information**
10. **Policy Updates**

### 12.2 User Rights Implementation Plan

#### **Account Dashboard Features**
- View collected data summary
- Download data export (JSON/CSV)
- Request account deletion
- Correct profile information
- Manage consent preferences

---

## 13. Roleplay vs Real Data Guidelines

### 13.1 Clear Distinction Needed
- **Terms of Service** should clarify fictional nature of roleplay content
- **Privacy Policy** should separate real vs fictional data processing
- **User Interface** should indicate when real data is being collected

### 13.2 User Education
- Help users understand difference between Steam profile (real) and character (fictional)
- Clear guidance on what information is shared vs kept private
- Roleplay conduct guidelines

---

## 14. Conclusion

The USS-VOYAGER website demonstrates **good technical security practices** and **minimal data collection**, which are positive for GDPR compliance. However, the site **lacks fundamental user rights implementation and legal framework** required under UK GDPR.

### 14.1 Compliance Status
- **Technical Security:** 🟢 Good
- **Data Minimization:** 🟢 Good  
- **User Rights:** 🔴 Non-compliant
- **Legal Framework:** 🔴 Missing
- **Consent Management:** 🔴 Missing

### 14.2 Priority Actions
1. **Create comprehensive privacy policy**
2. **Implement user data rights (access, deletion, export)**
3. **Establish data retention policies**
4. **Add clear consent mechanisms**
5. **Complete security improvements**

### 14.3 Timeline for Compliance
- **30 days:** Legal framework and basic user rights
- **90 days:** Full technical implementation
- **Ongoing:** Regular compliance monitoring

**Recommendation:** Engage data protection legal counsel for full compliance review and implementation guidance.

---

## 15. Contact & Next Steps

For GDPR compliance implementation:
1. Review this report with legal counsel
2. Prioritize immediate action items
3. Develop implementation timeline
4. Consider data protection officer appointment
5. Plan user communication about changes

**Report Prepared By:** AI Assistant  
**Technical Review:** Complete  
**Legal Review:** Required  
**Implementation Support:** Available  

---

*This report provides guidance based on analysis of the USS-VOYAGER website code and GDPR requirements. Legal counsel should be consulted for specific compliance implementation.*
