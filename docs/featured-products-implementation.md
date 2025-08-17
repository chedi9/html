# Featured Products Implementation

## Overview

This document describes the implementation of the modular featured products section for the WeBuy index page. The system displays products tagged as "المنتجات المميزة" (Featured Products) with optimized performance and scalability.

## Architecture

### Backend Components

1. **API Endpoint**: `api/featured-products.php`
   - Handles paginated requests for featured products
   - Prioritizes products from disabled sellers
   - Returns JSON responses with product data and pagination info

2. **Cache System**: `includes/featured_products_cache.php`
   - Provides file-based caching for improved performance
   - 5-minute cache duration with automatic cleanup
   - Cache keys based on page number and language

3. **Cache Cleanup**: `cache_cleanup.php`
   - Removes expired cache files
   - Can be run manually or via cron job

### Frontend Components

1. **JavaScript Module**: `js/featured-products.js`
   - Handles AJAX loading of products
   - Manages pagination and "Load More" functionality
   - Integrates with wishlist and cart systems
   - Provides responsive grid layout

2. **CSS Styles**: `css/components/featured-products.css`
   - RTL support for Arabic layout
   - Mobile-first responsive design
   - Dark theme support
   - Accessibility enhancements

3. **HTML Integration**: `index.php`
   - New section with ID `featured-products`
   - Uses existing translation system
   - Maintains consistent styling with other sections

## Features

### Core Functionality

- **Pagination**: 12 products per page with offset-based queries
- **Priority System**: Disabled sellers' products appear first
- **Multi-language Support**: Arabic, English, and French
- **Responsive Design**: Mobile-first approach with breakpoints
- **Performance Optimization**: Caching and lazy loading

### Product Display

- **Product Cards**: Consistent with existing design
- **Badges**: "جديد" (New) and "منتج من ذوي الإعاقة" (Product from disabled seller)
- **Ratings**: Star ratings with review counts
- **Wishlist Integration**: Add/remove from wishlist functionality
- **Add to Cart**: Direct purchase functionality

### User Experience

- **Loading States**: Skeleton loading and spinner indicators
- **Error Handling**: Graceful error messages
- **Accessibility**: ARIA labels and keyboard navigation
- **Progressive Enhancement**: Works without JavaScript

## Database Schema

### Featured Products Query

```sql
SELECT 
    p.*,
    s.is_disabled,
    s.name as seller_name,
    ds.name AS disabled_seller_name,
    ds.disability_type,
    ds.priority_level,
    CASE 
        WHEN ds.id IS NOT NULL THEN 1 
        WHEN p.is_priority_product = 1 THEN 2 
        ELSE 3 
    END as priority_order
FROM products p
LEFT JOIN sellers s ON p.seller_id = s.id
LEFT JOIN disabled_sellers ds ON p.disabled_seller_id = ds.id
WHERE p.approved = 1 
AND (p.is_priority_product = 1 OR ds.id IS NOT NULL)
ORDER BY priority_order ASC, ds.priority_level DESC, p.created_at DESC
```

### Priority Logic

1. **Level 1**: Products from disabled sellers (highest priority)
2. **Level 2**: Products marked as priority (`is_priority_product = 1`)
3. **Level 3**: Other approved products

## API Endpoints

### GET /api/featured-products.php

**Parameters:**
- `page` (optional): Page number (default: 1)
- `lang` (optional): Language code (ar/en/fr, default: session language)

**Response:**
```json
{
    "success": true,
    "data": {
        "products": [
            {
                "id": 1,
                "name": "Product Name",
                "description": "Product description",
                "price": "99.99",
                "image": {
                    "src": "optimized_image_url",
                    "srcset": "responsive_images",
                    "sizes": "image_sizes"
                },
                "is_new": true,
                "is_disabled_seller": true,
                "rating": {
                    "average": 4.5,
                    "count": 12
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 12,
            "total_products": 50,
            "total_pages": 5,
            "has_next_page": true,
            "has_prev_page": false
        }
    }
}
```

## Caching Strategy

### Cache Configuration

- **Duration**: 5 minutes
- **Storage**: File-based in `cache/featured_products/`
- **Keys**: Based on page number and language
- **Cleanup**: Automatic expiration and manual cleanup script

### Cache Files

- Format: `featured_products_page_{page}_lang_{lang}.json`
- Example: `featured_products_page_1_lang_ar.json`

### Cache Management

```bash
# Manual cleanup
php cache_cleanup.php

# Cron job (recommended)
0 */10 * * * /usr/bin/php /path/to/webuy/cache_cleanup.php
```

## Performance Optimizations

### Backend

- **Database Indexing**: Ensure indexes on `approved`, `is_priority_product`, `disabled_seller_id`
- **Query Optimization**: Single query with JOINs instead of multiple queries
- **Caching**: File-based cache with 5-minute TTL
- **Pagination**: Offset-based with reasonable page sizes

### Frontend

- **Lazy Loading**: Images load only when needed
- **Skeleton Loading**: Placeholder content while loading
- **Progressive Enhancement**: Works without JavaScript
- **Responsive Images**: Optimized image sizes for different devices

## Accessibility Features

### ARIA Labels

- `aria-live="polite"` for dynamic content updates
- `aria-label` for interactive elements
- Semantic HTML structure

### Keyboard Navigation

- Focus management for dynamic content
- Keyboard shortcuts for wishlist functionality
- Skip links for main content

### Screen Reader Support

- Proper heading hierarchy
- Alt text for images
- Descriptive link text

## Future Enhancements

### Planned Features

1. **Infinite Scroll**: Replace "Load More" with infinite scroll
2. **Category Filters**: Filter by product categories
3. **Search Integration**: Search within featured products
4. **Personalization**: User-specific product recommendations
5. **Analytics**: Track user interactions and performance metrics

### Technical Improvements

1. **Redis Caching**: Replace file-based cache with Redis
2. **CDN Integration**: Serve images from CDN
3. **Service Workers**: Offline support and background sync
4. **GraphQL**: More flexible API with GraphQL
5. **Microservices**: Separate featured products service

## Maintenance

### Regular Tasks

1. **Cache Cleanup**: Run `cache_cleanup.php` periodically
2. **Performance Monitoring**: Monitor API response times
3. **Error Logging**: Check for API errors in logs
4. **Database Optimization**: Regular database maintenance

### Troubleshooting

1. **Cache Issues**: Clear cache directory if needed
2. **API Errors**: Check database connectivity and permissions
3. **JavaScript Errors**: Verify browser console for errors
4. **Performance Issues**: Monitor server resources and database queries

## Security Considerations

1. **Input Validation**: All parameters are validated and sanitized
2. **SQL Injection**: Using prepared statements
3. **XSS Prevention**: Output encoding for user-generated content
4. **CSRF Protection**: Session-based security
5. **Rate Limiting**: Consider implementing rate limiting for API

## Testing

### Manual Testing

1. **Responsive Design**: Test on various screen sizes
2. **Language Switching**: Verify translations work correctly
3. **Pagination**: Test "Load More" functionality
4. **Wishlist**: Test add/remove from wishlist
5. **Accessibility**: Test with screen readers

### Automated Testing

1. **API Tests**: Test endpoint responses and error handling
2. **JavaScript Tests**: Test module functionality
3. **Performance Tests**: Load testing for API endpoints
4. **Accessibility Tests**: Automated accessibility checking

## Deployment

### Files to Deploy

1. `api/featured-products.php`
2. `includes/featured_products_cache.php`
3. `js/featured-products.js`
4. `css/components/featured-products.css`
5. `cache_cleanup.php`
6. Updated `index.php`
7. Updated language files (`lang/ar.php`, `lang/en.php`, `lang/fr.php`)

### Post-Deployment

1. **Create Cache Directory**: Ensure `cache/featured_products/` exists
2. **Set Permissions**: Set proper file permissions for cache directory
3. **Test Functionality**: Verify all features work correctly
4. **Monitor Performance**: Check for any performance issues
5. **Update Documentation**: Update any relevant documentation