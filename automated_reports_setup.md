# 📊 Automated Seller Analytics Reports System

## 🎯 Overview

The WeBuy Automated Analytics Reports System provides intelligent, data-driven insights to sellers automatically. It sends personalized reports with actionable recommendations to help sellers improve their performance.

## ✨ Features

### 📈 **Advanced Analytics**
- **Product Performance Analysis**: Track sales, stock levels, and approval status
- **Order Analytics**: Revenue, average order value, unique customers
- **Review Insights**: Rating analysis, positive/negative review percentages
- **Top Products**: Identify best-performing products with detailed stats

### 🧠 **Intelligent Insights**
- **Automated Recommendations**: AI-powered suggestions based on data
- **Performance Warnings**: Alerts for low stock, pending approvals, poor ratings
- **Growth Opportunities**: Suggestions for improving conversion rates and sales
- **Period-Specific Advice**: Tailored recommendations for daily, weekly, monthly, yearly reports

### 📧 **Professional Email Reports**
- **Beautiful HTML Templates**: Modern, responsive email design
- **Personalized Content**: Customized for each seller with their specific data
- **Actionable CTAs**: Direct links to seller dashboard and product management
- **Multi-language Support**: Full Arabic support with RTL layout

### ⚡ **Automated Scheduling**
- **Daily Reports**: Sent at 9:00 AM every day
- **Weekly Reports**: Sent every Monday at 10:00 AM
- **Monthly Reports**: Sent on the 1st of each month at 11:00 AM
- **Yearly Reports**: Sent on January 1st at 12:00 PM

## 🚀 Setup Instructions

### 1. **Database Setup**

Run the SQL script to update the email_campaigns table:

```sql
-- Update email_campaigns table to support analytics_report type
ALTER TABLE email_campaigns MODIFY COLUMN type ENUM('price_reduction', 'wishlist_promo', 'newsletter', 'seller_tips', 'analytics_report') NOT NULL;
```

### 2. **Cron Job Setup**

Add these cron jobs to your server:

#### **Option A: Command Line Cron (Recommended)**
```bash
# Daily reports at 9:00 AM
0 9 * * * /usr/bin/php /path/to/your/website/admin/automated_reports.php

# Weekly reports every Monday at 10:00 AM
0 10 * * 1 /usr/bin/php /path/to/your/website/admin/automated_reports.php

# Monthly reports on 1st of month at 11:00 AM
0 11 1 * * /usr/bin/php /path/to/your/website/admin/automated_reports.php

# Yearly reports on January 1st at 12:00 PM
0 12 1 1 * /usr/bin/php /path/to/your/website/admin/automated_reports.php
```

#### **Option B: Web-based Cron (Alternative)**
If you can't access command line cron, use a web-based cron service:

**URL to call:**
```
https://yourdomain.com/admin/automated_reports.php?cron_key=webuy_automated_reports_2024
```

**Services you can use:**
- [cron-job.org](https://cron-job.org)
- [EasyCron](https://www.easycron.com)
- [SetCronJob](https://www.setcronjob.com)

### 3. **Configuration**

Edit `admin/automated_reports.php` to customize:

```php
$config = [
    'daily' => [
        'enabled' => true,
        'time' => '09:00', // Change time as needed
        'sellers' => 'all'
    ],
    'weekly' => [
        'enabled' => true,
        'day' => 'monday', // Change day as needed
        'time' => '10:00',
        'sellers' => 'all'
    ],
    // ... more configuration
];
```

## 📊 Report Types

### **Daily Reports**
- **Focus**: Yesterday's performance
- **Content**: Orders, revenue, reviews from previous day
- **Insights**: Quick wins, immediate actions needed
- **Timing**: 9:00 AM daily

### **Weekly Reports**
- **Focus**: Last 7 days performance
- **Content**: Weekly trends, product performance, customer behavior
- **Insights**: Weekly planning, inventory management
- **Timing**: Every Monday at 10:00 AM

### **Monthly Reports**
- **Focus**: Last 30 days performance
- **Content**: Monthly trends, revenue analysis, growth metrics
- **Insights**: Strategic planning, seasonal adjustments
- **Timing**: 1st of each month at 11:00 AM

### **Yearly Reports**
- **Focus**: Last 365 days performance
- **Content**: Annual summary, growth analysis, achievements
- **Insights**: Long-term strategy, goal setting
- **Timing**: January 1st at 12:00 PM

## 🎨 Email Template Features

### **Professional Design**
- **Gradient Header**: WeBuy branding with modern design
- **Statistics Grid**: Clear, visual representation of key metrics
- **Insights Section**: Color-coded recommendations (warning, info, success)
- **Top Products**: Highlight best-performing items
- **Call-to-Action Buttons**: Direct links to seller dashboard

### **Personalized Content**
- **Seller Name**: Personalized greeting
- **Store Name**: Branded throughout the report
- **Custom Messages**: Admin can add personalized notes
- **Period-Specific Data**: Relevant statistics for the report period

## 🔧 Manual Usage

### **Admin Panel Access**
1. Go to Admin Dashboard
2. Click "تحليلات البائعين" (Seller Analytics)
3. Select report type (daily, weekly, monthly, yearly)
4. Choose sellers to send reports to
5. Add optional custom message
6. Click "إرسال التقارير" (Send Reports)

### **Preview Feature**
- Click "معاينة التقرير" (Preview Report) to see how the email will look
- Opens in new window with sample data
- Helps verify design and content before sending

## 📈 Analytics Included

### **Product Analytics**
- Total products count
- Approved vs pending products
- Average, minimum, maximum prices
- Total stock levels
- Low stock warnings

### **Sales Analytics**
- Total orders in period
- Total revenue generated
- Average order value
- Unique customers count
- Conversion rate analysis

### **Review Analytics**
- Total reviews received
- Average rating score
- Positive vs negative review percentages
- Rating improvement suggestions

### **Performance Analytics**
- Top 5 performing products
- Sales count per product
- Quantity sold per product
- Product-specific ratings

## 🧠 Intelligent Insights

### **Warning Insights**
- ⚠️ **Pending Products**: Products awaiting approval
- ⚠️ **Low Stock**: Inventory running low
- ⚠️ **Poor Ratings**: Below-average customer satisfaction

### **Information Insights**
- ℹ️ **Conversion Rate**: Product-to-sales conversion analysis
- ℹ️ **Order Value**: Average order value optimization
- ℹ️ **Review Quality**: Customer satisfaction improvement

### **Success Insights**
- ✅ **Best Performers**: Top-selling products
- ✅ **Growth Opportunities**: Areas for expansion
- ✅ **Achievement Recognition**: Milestones reached

## 🔒 Security Features

### **Access Control**
- Admin authentication required
- Cron key protection for web access
- IP logging for security monitoring

### **Data Protection**
- Secure email transmission via SMTP
- Encrypted database connections
- Activity logging for audit trails

## 📝 Logging & Monitoring

### **Activity Logs**
- All report sends logged to `activity_log` table
- Email campaign tracking in `email_campaigns` table
- File-based logging in `automated_reports.log`

### **Error Handling**
- Comprehensive error catching and logging
- Graceful fallbacks for missing data
- Email delivery confirmation

## 🚀 Performance Optimization

### **Efficient Queries**
- Optimized SQL queries with proper joins
- Indexed database columns for fast retrieval
- Minimal database load during report generation

### **Email Delivery**
- 1-second delays between emails to prevent server overload
- SMTP connection reuse for efficiency
- Fallback to mail() function if PHPMailer fails

## 🎯 Benefits for Sellers

### **Data-Driven Decisions**
- Clear understanding of performance metrics
- Actionable insights for improvement
- Trend analysis for strategic planning

### **Performance Optimization**
- Identify underperforming products
- Optimize pricing strategies
- Improve customer satisfaction

### **Growth Opportunities**
- Discover best-selling products
- Understand customer behavior
- Plan inventory and marketing

## 🔧 Troubleshooting

### **Common Issues**

1. **Reports not sending**
   - Check cron job configuration
   - Verify email credentials in `email_helper.php`
   - Check server logs for errors

2. **Missing data in reports**
   - Ensure database tables exist and have data
   - Check date ranges for report periods
   - Verify seller data integrity

3. **Email delivery issues**
   - Test email configuration manually
   - Check SMTP settings and credentials
   - Verify server email capabilities

### **Testing**

Test the system manually:
```bash
# Test via command line
php admin/automated_reports.php

# Test via web (replace with your domain)
https://yourdomain.com/admin/automated_reports.php?cron_key=webuy_automated_reports_2024
```

## 📞 Support

For technical support or customization requests:
- Check the log files for detailed error information
- Review the configuration settings
- Test individual components separately

---

**🎉 Congratulations!** Your WeBuy marketplace now has a powerful, automated analytics system that will help sellers grow their businesses with data-driven insights! 