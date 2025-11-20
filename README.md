# Fluent Bookings - WordPress Appointment Booking Plugin

**Version:** 1.0.0
**Author:** Akash
**Requires at least:** WordPress 5.0
**Tested up to:** WordPress 6.4
**Requires PHP:** 7.4 - 8.4
**License:** GPL v2 or later

## Description

Fluent Bookings is a powerful and feature-rich WordPress appointment booking plugin designed to help you manage appointments, bookings, and schedules with ease. Built with modern web technologies and best practices, it offers a seamless experience for both administrators and customers.

## ✨ Features

### Phase 1 (Completed)

#### 🎨 Drag-and-Drop Form Builder
- Visual form builder with intuitive drag-and-drop interface
- 10+ field types (text, email, phone, date, time, textarea, select, radio, checkbox, number)
- Field customization options:
  - Label and placeholder text
  - Required/optional fields
  - Show/hide labels
  - Field width (25%, 50%, 75%, 100%)
  - Custom field ordering

#### 📝 Unlimited Appointment Forms
- Create unlimited booking forms
- Each form gets a unique shortcode: `[fluent_booking id="X"]`
- Separate settings for each form
- Duplicate forms with one click
- Active/inactive status management

#### 🎨 Advanced Style Customization
- **Form Container Styling:**
  - Background color
  - Border color, width, and radius
  - Padding control

- **Label Styling:**
  - Text color
  - Font size and weight
  - Custom font family support

- **Input Field Styling:**
  - Background and text color
  - Border customization
  - Padding and border radius
  - Font size and family

- **Submit Button Styling:**
  - Background and text color
  - Border radius and padding
  - Font size and weight

#### ⚙️ Form Settings
- Appointment duration (5-480 minutes)
- Buffer time between appointments
- Minimum booking notice (hours)
- Maximum booking advance (days)
- Custom confirmation messages
- Success actions (message or redirect)

#### 📧 Email Notifications
- Customer confirmation emails
- Admin notification emails
- Customizable email templates
- Merge tags support:
  - `{{customer_name}}`
  - `{{customer_email}}`
  - `{{customer_phone}}`
  - `{{booking_date}}`
  - `{{booking_time}}`
  - `{{booking_id}}`
  - `{{site_name}}`
  - `{{site_url}}`

#### 📅 Smart Availability System
- Real-time slot availability checking
- Automatic prevention of double bookings
- Day-of-week availability rules
- Time slot generation based on duration
- Blocked dates and times support

#### 📊 Booking Management
- List view with filters:
  - Filter by form
  - Filter by status
  - Date range filtering
  - Customer search
- Quick status updates (Pending, Confirmed, Cancelled, Completed)
- Export bookings to CSV
- Pagination support
- Booking details view
- Delete bookings

#### 🎯 Clean Admin Interface
- Dashboard with statistics:
  - Active forms count
  - Total bookings
  - Pending bookings
  - Today's bookings
- Recent bookings overview
- Quick action links
- Responsive design

## 📁 File Structure

```
fluent-bookings/
├── fluent-bookings.php          # Main plugin file
├── README.md                     # Documentation
├── admin/                        # Admin area files
│   ├── assets/
│   │   ├── css/
│   │   │   ├── admin.css        # Admin styles
│   │   │   └── form-builder.css # Form builder styles
│   │   └── js/
│   │       ├── admin.js         # Admin JavaScript
│   │       └── form-builder.js  # Form builder JavaScript
│   ├── views/                   # Admin page templates
│   │   ├── dashboard.php
│   │   ├── forms-list.php
│   │   ├── form-builder.php
│   │   ├── bookings-list.php
│   │   ├── calendar.php
│   │   ├── customers.php
│   │   └── settings.php
│   ├── class-admin-menu.php     # Admin menu handler
│   ├── class-form-builder.php   # Form builder logic
│   └── class-booking-manager.php # Booking management
├── public/                       # Frontend files
│   ├── assets/
│   │   ├── css/
│   │   │   └── public.css       # Frontend styles
│   │   └── js/
│   │       └── public.js        # Frontend JavaScript
│   ├── class-shortcode.php      # Shortcode handler
│   └── class-form-handler.php   # Form submission handler
├── includes/                     # Core classes
│   ├── class-database.php       # Database management
│   ├── class-booking.php        # Booking operations
│   ├── class-email.php          # Email handling
│   ├── class-availability.php   # Availability management
│   └── class-helper.php         # Helper functions
└── languages/                    # Translation files
```

## 🗄️ Database Tables

The plugin creates 7 custom database tables:

1. **wp_fluentbooking_forms** - Stores form configurations
2. **wp_fluentbooking_bookings** - Stores booking records
3. **wp_fluentbooking_availability** - Stores availability rules
4. **wp_fluentbooking_blocked_dates** - Stores blocked dates/times
5. **wp_fluentbooking_customers** - Stores customer information
6. **wp_fluentbooking_notifications** - Stores email templates
7. **wp_fluentbooking_meta** - Stores additional metadata

## 🚀 Installation

1. Upload the `fluent-bookings` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Fluent Bookings' in the admin menu
4. Create your first appointment form
5. Copy the shortcode and paste it into any page or post

## 💻 Usage

### Creating a Form

1. Go to **Fluent Bookings → Add New Form**
2. Enter form title and description
3. Drag fields from the left panel to build your form
4. Click on fields to customize them (label, placeholder, width, required, etc.)
5. Configure form settings (duration, buffer time, etc.)
6. Customize styles to match your theme
7. Set up email notifications
8. Click "Create Form"

### Adding Form to a Page

#### Using Shortcode:
```php
[fluent_booking id="1"]
```

#### Using PHP:
```php
<?php echo do_shortcode('[fluent_booking id="1"]'); ?>
```

#### In Template Files:
```php
<?php
if (function_exists('fluent_booking')) {
    echo do_shortcode('[fluent_booking id="1"]');
}
?>
```

### Managing Bookings

1. Go to **Fluent Bookings → All Bookings**
2. Filter bookings by form, status, date range, or search customers
3. Update booking status by clicking the dropdown
4. Export bookings to CSV for reporting
5. Delete bookings as needed

### Setting Availability

Default availability is Monday-Friday, 9 AM - 5 PM with 30-minute slots. Availability rules are created automatically when you create a new form.

## 🎨 Customization

### Custom Styles

All styles are scoped to the form container to avoid conflicts:

```css
#fb-form-123 .fluent-booking-form {
    /* Your custom styles */
}
```

### Hooks and Filters

The plugin is built with extensibility in mind. Future versions will include WordPress hooks and filters for developers.

## 🔧 Technical Details

### PHP Compatibility
- **Minimum:** PHP 7.4
- **Maximum:** PHP 8.4
- **Error Free:** No warnings, notices, or deprecated function calls

### JavaScript
- Uses **jQuery** (not vanilla JavaScript as specified)
- All jQuery code uses `jQuery()` instead of `$` to avoid conflicts
- Scoped to parent containers to avoid global conflicts

### CSS
- All CSS is scoped to plugin-specific classes
- No conflicts with theme styles
- Responsive and mobile-friendly

### Security
- Nonce verification on all AJAX requests
- Capability checks for admin actions
- Prepared SQL statements to prevent SQL injection
- Input sanitization and validation
- Output escaping for XSS protection

### Performance
- Optimized database queries
- Assets loaded only when needed
- Minimal HTTP requests
- Clean, well-documented code

## 📋 Upcoming Features (Phase 2-5)

### Phase 2: Advanced Booking Management
- Calendar view (month/week/day)
- Visual calendar with drag-and-drop
- Customer management system
- Booking history per customer

### Phase 3: Google Calendar Integration
- Two-way sync with Google Calendar
- OAuth setup wizard
- Auto-create events on booking
- Multiple calendar support

### Phase 4: Elementor Integration
- Elementor widget for forms
- Visual form builder in Elementor
- Widget customization options

### Phase 5: Additional Features
- Payment integration (PayPal, Stripe)
- SMS notifications (Twilio)
- Zoom meeting integration
- Email reminders
- Webhooks and REST API
- Multi-service support
- Staff assignment
- Reporting and analytics

## 🐛 Known Issues

None at this time. Please report issues to the plugin author.

## 📝 Changelog

### Version 1.0.0 (Phase 1)
- Initial release
- Drag-and-drop form builder
- Unlimited appointment forms
- Advanced style customization
- Email notifications
- Smart availability system
- Booking management (list view)
- Export to CSV
- Shortcode support

## 🤝 Support

For support, please contact the plugin author or submit issues through your preferred channel.

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👨‍💻 Developer Notes

### Code Standards
- Follows WordPress Coding Standards
- PSR-4 autoloading structure (class-based)
- Well-documented with inline comments
- Object-oriented design patterns

### Database Access
- Uses `$wpdb` for all database operations
- Prepared statements for security
- Proper indexing for performance

### AJAX Requests
- All AJAX uses WordPress admin-ajax.php
- Nonce verification required
- Capability checks on admin actions
- Proper error handling and responses

### Extending the Plugin
The plugin is designed to be extensible. Core functionality is separated into logical classes that can be extended or modified.

---

**Enjoy using Fluent Bookings! 🎉**
