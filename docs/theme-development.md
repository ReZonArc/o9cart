# Theme Development Guide

## Table of Contents

1. [Theme Overview](#theme-overview)
2. [Theme Structure](#theme-structure) 
3. [Creating a Custom Theme](#creating-a-custom-theme)
4. [Template System](#template-system)
5. [Asset Management](#asset-management)
6. [Responsive Design](#responsive-design)
7. [Theme Configuration](#theme-configuration)
8. [Best Practices](#best-practices)
9. [Theme Packaging](#theme-packaging)

## Theme Overview

O9Cart uses the Twig templating engine for themes, providing a powerful and flexible theming system that separates design from logic.

### Theme Architecture

```mermaid
graph TB
    subgraph "Theme Structure"
        A[Template Files (.twig)]
        B[Stylesheets (.css/.scss)]
        C[JavaScript Files]
        D[Images & Assets]
        E[Configuration Files]
    end
    
    subgraph "Template Engine"
        F[Twig Engine]
        G[Template Loader]
        H[Cache System]
    end
    
    subgraph "Data Sources"
        I[Controllers]
        J[Models]
        K[Language Files]
    end
    
    A --> F
    B --> F
    C --> F
    D --> F
    E --> F
    
    F --> G
    G --> H
    
    I --> F
    J --> F
    K --> F
```

## Theme Structure

### Default Theme Directory Structure

```
upload/catalog/view/theme/default/
├── template/
│   ├── common/
│   │   ├── header.twig
│   │   ├── footer.twig
│   │   └── menu.twig
│   ├── product/
│   │   ├── product.twig
│   │   ├── category.twig
│   │   └── search.twig
│   ├── checkout/
│   │   ├── cart.twig
│   │   ├── checkout.twig
│   │   └── success.twig
│   └── account/
│       ├── login.twig
│       ├── register.twig
│       └── account.twig
├── stylesheet/
│   ├── bootstrap.min.css
│   ├── font-awesome.min.css
│   └── stylesheet.css
├── javascript/
│   ├── bootstrap.min.js
│   ├── common.js
│   └── product.js
├── image/
│   ├── logo.png
│   ├── icons/
│   └── backgrounds/
└── config.php
```

### Admin Theme Structure

```
upload/admin/view/template/
├── common/
│   ├── header.twig
│   ├── footer.twig
│   ├── menu.twig
│   └── dashboard.twig
├── catalog/
│   ├── product_form.twig
│   ├── product_list.twig
│   └── category_form.twig
├── sale/
│   ├── order_list.twig
│   ├── order_info.twig
│   └── customer_list.twig
└── system/
    ├── user_form.twig
    ├── setting.twig
    └── log.twig
```

## Creating a Custom Theme

### Step 1: Create Theme Directory

```bash
# Create new theme directory
mkdir -p upload/catalog/view/theme/mytheme

# Copy default theme as starting point
cp -r upload/catalog/view/theme/default/* upload/catalog/view/theme/mytheme/
```

### Step 2: Theme Configuration

Create `upload/catalog/view/theme/mytheme/config.php`:

```php
<?php
// Theme configuration
return [
    'name' => 'My Custom Theme',
    'version' => '1.0.0',
    'author' => 'Your Name',
    'description' => 'A custom theme for O9Cart',
    'compatibility' => '4.0+',
    
    // Theme settings
    'settings' => [
        'color_scheme' => 'blue',
        'layout_width' => '1200px',
        'enable_animations' => true,
        'show_breadcrumbs' => true,
        'products_per_row' => 4
    ],
    
    // Asset dependencies
    'assets' => [
        'css' => [
            'stylesheet/bootstrap.min.css',
            'stylesheet/font-awesome.min.css',
            'stylesheet/stylesheet.css'
        ],
        'js' => [
            'javascript/jquery.min.js',
            'javascript/bootstrap.min.js',
            'javascript/common.js'
        ]
    ]
];
```

### Step 3: Activate Theme

In admin panel: `System > Settings > Store > Theme` or modify config directly:

```php
// In upload/config.php
define('THEME_DEFAULT', 'mytheme');
```

## Template System

### Twig Template Basics

O9Cart templates use Twig syntax:

```twig
{# template/common/header.twig #}
<!DOCTYPE html>
<html dir="{{ direction }}" lang="{{ lang }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ title }}</title>
    <base href="{{ base }}" />
    
    {% if description %}
        <meta name="description" content="{{ description }}" />
    {% endif %}
    
    {% if keywords %}
        <meta name="keywords" content="{{ keywords }}" />
    {% endif %}
    
    {# Load CSS files #}
    {% for style in styles %}
        <link href="{{ style.href }}" type="text/css" rel="{{ style.rel }}" media="{{ style.media }}" />
    {% endfor %}
    
    {# Load JavaScript files #}
    {% for script in scripts %}
        <script src="{{ script }}" type="text/javascript"></script>
    {% endfor %}
</head>
<body>
```

### Template Inheritance

Use template inheritance for consistency:

```twig
{# template/common/base.twig #}
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Default Title{% endblock %}</title>
    {% block stylesheets %}
        <link href="stylesheet/style.css" rel="stylesheet">
    {% endblock %}
</head>
<body>
    <header>
        {% block header %}{% endblock %}
    </header>
    
    <main>
        {% block content %}{% endblock %}
    </main>
    
    <footer>
        {% block footer %}{% endblock %}
    </footer>
    
    {% block javascripts %}{% endblock %}
</body>
</html>
```

```twig
{# template/product/product.twig #}
{% extends "common/base.twig" %}

{% block title %}{{ heading_title }}{% endblock %}

{% block content %}
<div class="container">
    <div class="row">
        <div class="col-sm-6">
            <div class="product-image">
                <img src="{{ thumb }}" alt="{{ heading_title }}" class="img-responsive">
            </div>
        </div>
        <div class="col-sm-6">
            <div class="product-info">
                <h1>{{ heading_title }}</h1>
                <p class="price">{{ price }}</p>
                <div class="description">{{ description }}</div>
                
                <button type="button" class="btn btn-primary btn-lg btn-block" onclick="cart.add('{{ product_id }}');">
                    {{ button_cart }}
                </button>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

### Template Macros

Create reusable components with macros:

```twig
{# template/common/macros.twig #}
{% macro product_card(product) %}
<div class="product-card">
    <div class="product-image">
        <a href="{{ product.href }}">
            <img src="{{ product.thumb }}" alt="{{ product.name }}" class="img-responsive">
        </a>
    </div>
    <div class="product-info">
        <h4><a href="{{ product.href }}">{{ product.name }}</a></h4>
        <p class="price">
            {% if product.special %}
                <span class="price-old">{{ product.price }}</span>
                <span class="price-new">{{ product.special }}</span>
            {% else %}
                <span class="price-regular">{{ product.price }}</span>
            {% endif %}
        </p>
        <button type="button" class="btn btn-cart" onclick="cart.add('{{ product.product_id }}');">
            Add to Cart
        </button>
    </div>
</div>
{% endmacro %}
```

Use macros in templates:

```twig
{# template/product/category.twig #}
{% import "common/macros.twig" as macros %}

<div class="product-grid">
    {% for product in products %}
        <div class="col-md-3 col-sm-6">
            {{ macros.product_card(product) }}
        </div>
    {% endfor %}
</div>
```

## Asset Management

### CSS/SCSS Organization

```scss
// stylesheet/scss/main.scss
// Variables
@import 'variables';

// Base styles
@import 'base';
@import 'typography';

// Components
@import 'components/buttons';
@import 'components/forms';
@import 'components/cards';

// Layout
@import 'layout/header';
@import 'layout/footer';
@import 'layout/sidebar';

// Pages
@import 'pages/product';
@import 'pages/checkout';
@import 'pages/account';

// Responsive
@import 'responsive';
```

### SCSS Variables

```scss
// stylesheet/scss/_variables.scss
// Colors
$primary-color: #337ab7;
$secondary-color: #5cb85c;
$danger-color: #d9534f;
$warning-color: #f0ad4e;

// Typography
$font-family-base: 'Helvetica Neue', Helvetica, Arial, sans-serif;
$font-size-base: 14px;
$line-height-base: 1.42857143;

// Layout
$container-max-width: 1200px;
$grid-gutter: 30px;

// Breakpoints
$screen-xs: 480px;
$screen-sm: 768px;
$screen-md: 992px;
$screen-lg: 1200px;
```

### JavaScript Organization

```javascript
// javascript/theme.js
var O9Theme = {
    // Initialize theme
    init: function() {
        this.initNavigation();
        this.initProductGallery();
        this.initCart();
        this.initSearch();
    },
    
    // Navigation
    initNavigation: function() {
        $('.dropdown-toggle').dropdown();
        
        // Mobile menu
        $('.mobile-menu-toggle').on('click', function() {
            $('.mobile-menu').toggleClass('active');
        });
    },
    
    // Product gallery
    initProductGallery: function() {
        if ($('.product-gallery').length) {
            $('.product-gallery').magnificPopup({
                type: 'image',
                gallery: {
                    enabled: true
                }
            });
        }
    },
    
    // Shopping cart
    initCart: function() {
        // Add to cart
        $(document).on('click', '.btn-cart', function(e) {
            e.preventDefault();
            
            var productId = $(this).data('product-id');
            var quantity = $('#quantity').val() || 1;
            
            cart.add(productId, quantity);
        });
        
        // Update cart
        $('#cart').on('shown.bs.dropdown', function () {
            $('#cart').load('index.php?route=common/cart/info');
        });
    },
    
    // Search functionality
    initSearch: function() {
        $('#search input[name=\'search\']').on('keydown', function(e) {
            if (e.keyCode == 13) {
                var search = $(this).val();
                if (search) {
                    location = 'index.php?route=product/search&search=' + encodeURIComponent(search);
                }
            }
        });
    }
};

// Initialize when document ready
$(document).ready(function() {
    O9Theme.init();
});
```

## Responsive Design

### Mobile-First Approach

```scss
// Base mobile styles
.product-grid {
    .product-item {
        width: 100%;
        margin-bottom: 20px;
    }
}

// Tablet styles
@media (min-width: $screen-sm) {
    .product-grid {
        .product-item {
            width: 50%;
            float: left;
        }
    }
}

// Desktop styles
@media (min-width: $screen-md) {
    .product-grid {
        .product-item {
            width: 33.333%;
        }
    }
}

// Large desktop
@media (min-width: $screen-lg) {
    .product-grid {
        .product-item {
            width: 25%;
        }
    }
}
```

### Responsive Images

```twig
{# Responsive product images #}
<div class="product-image">
    <img src="{{ product.image }}" 
         srcset="{{ product.image_2x }} 2x"
         alt="{{ product.name }}" 
         class="img-responsive">
</div>

{# Picture element for art direction #}
<picture class="hero-image">
    <source media="(max-width: 768px)" srcset="{{ hero_mobile }}">
    <source media="(max-width: 1200px)" srcset="{{ hero_tablet }}">
    <img src="{{ hero_desktop }}" alt="Hero Image">
</picture>
```

## Theme Configuration

### Theme Settings Panel

```php
// admin/controller/extension/theme/mytheme.php
<?php
class ControllerExtensionThemeMytheme extends Controller {
    public function index() {
        $this->load->language('extension/theme/mytheme');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // Handle form submission
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('theme_mytheme', $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=theme'));
        }
        
        // Load settings
        $data['theme_mytheme_color'] = $this->config->get('theme_mytheme_color') ?: 'blue';
        $data['theme_mytheme_layout_width'] = $this->config->get('theme_mytheme_layout_width') ?: '1200px';
        $data['theme_mytheme_products_per_row'] = $this->config->get('theme_mytheme_products_per_row') ?: 4;
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/theme/mytheme', $data));
    }
}
```

### Theme Options Template

```twig
{# admin/view/template/extension/theme/mytheme.twig #}
{{ header }}{{ column_left }}
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <h1>{{ heading_title }}</h1>
        </div>
    </div>
    
    <div class="container-fluid">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Theme Settings</h3>
            </div>
            <div class="panel-body">
                <form action="{{ action }}" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Color Scheme</label>
                        <select name="theme_mytheme_color" class="form-control">
                            <option value="blue"{% if theme_mytheme_color == 'blue' %} selected{% endif %}>Blue</option>
                            <option value="green"{% if theme_mytheme_color == 'green' %} selected{% endif %}>Green</option>
                            <option value="red"{% if theme_mytheme_color == 'red' %} selected{% endif %}>Red</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Layout Width</label>
                        <input type="text" name="theme_mytheme_layout_width" 
                               value="{{ theme_mytheme_layout_width }}" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Products Per Row</label>
                        <input type="number" name="theme_mytheme_products_per_row" 
                               value="{{ theme_mytheme_products_per_row }}" 
                               min="2" max="6" class="form-control">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
{{ footer }}
```

## Best Practices

### Performance Optimization

```scss
// Use efficient CSS selectors
.product-card { } // Good
div.product-card { } // Less efficient
.sidebar .menu ul li a { } // Avoid deep nesting

// Minimize HTTP requests
@import 'components'; // Combine files
```

```javascript
// Minimize DOM queries
var $productCards = $('.product-card'); // Cache selectors

// Use event delegation
$(document).on('click', '.btn-cart', function() {
    // Handle click
});

// Lazy load images
$('.lazy-image').lazyload();
```

### Accessibility

```twig
{# Semantic HTML #}
<nav role="navigation" aria-label="Main navigation">
    <ul>
        {% for category in categories %}
            <li><a href="{{ category.href }}">{{ category.name }}</a></li>
        {% endfor %}
    </ul>
</nav>

{# Alt text for images #}
<img src="{{ product.image }}" 
     alt="{{ product.name }}" 
     title="{{ product.name }}">

{# Form labels #}
<label for="search-input">Search Products</label>
<input type="text" id="search-input" name="search">

{# ARIA attributes #}
<button aria-expanded="false" 
        aria-controls="mobile-menu" 
        class="menu-toggle">
    Menu
</button>
```

### SEO Optimization

```twig
{# Structured data #}
<script type="application/ld+json">
{
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": "{{ product.name }}",
    "image": "{{ product.image }}",
    "description": "{{ product.description }}",
    "sku": "{{ product.sku }}",
    "brand": {
        "@type": "Brand",
        "name": "{{ product.manufacturer }}"
    },
    "offers": {
        "@type": "Offer",
        "url": "{{ product.href }}",
        "priceCurrency": "{{ currency }}",
        "price": "{{ product.price_raw }}",
        "availability": "https://schema.org/InStock"
    }
}
</script>

{# Meta tags #}
<meta property="og:title" content="{{ product.name }}">
<meta property="og:description" content="{{ product.description }}">
<meta property="og:image" content="{{ product.image }}">
<meta property="og:url" content="{{ product.href }}">
```

## Theme Packaging

### Create Installation Package

```bash
#!/bin/bash
# package-theme.sh

THEME_NAME="mytheme"
VERSION="1.0.0"
PACKAGE_NAME="${THEME_NAME}-${VERSION}"

# Create package directory
mkdir -p packages/${PACKAGE_NAME}

# Copy theme files
cp -r upload/catalog/view/theme/${THEME_NAME} packages/${PACKAGE_NAME}/upload/catalog/view/theme/

# Copy admin files (if any)
if [ -d "upload/admin/view/template/extension/theme/${THEME_NAME}" ]; then
    mkdir -p packages/${PACKAGE_NAME}/upload/admin/view/template/extension/theme/
    cp -r upload/admin/view/template/extension/theme/${THEME_NAME} packages/${PACKAGE_NAME}/upload/admin/view/template/extension/theme/
fi

# Create install.xml
cat > packages/${PACKAGE_NAME}/install.xml << EOF
<?xml version="1.0" encoding="utf-8"?>
<modification>
    <name>${THEME_NAME} Theme</name>
    <version>${VERSION}</version>
    <author>Your Name</author>
    <link>https://yourwebsite.com</link>
</modification>
EOF

# Create package
cd packages
zip -r ${PACKAGE_NAME}.ocmod.zip ${PACKAGE_NAME}/
rm -rf ${PACKAGE_NAME}

echo "Package created: packages/${PACKAGE_NAME}.ocmod.zip"
```

### Theme Documentation

Create `README.md` for your theme:

```markdown
# MyTheme for O9Cart

A modern, responsive theme for O9Cart e-commerce platform.

## Features

- Fully responsive design
- Customizable color schemes
- SEO optimized
- Fast loading
- Accessibility compliant

## Installation

1. Download the theme package
2. Upload via Extension Installer
3. Go to System > Settings > Store
4. Select MyTheme from Theme dropdown
5. Save settings

## Configuration

Access theme settings via Extensions > Themes > MyTheme

## Support

For support, please visit: https://yourwebsite.com/support

## Changelog

### 1.0.0
- Initial release
```

This comprehensive theme development guide should help developers create professional, maintainable themes for O9Cart. The combination of Twig templating, SCSS preprocessing, and modern JavaScript provides a powerful foundation for custom e-commerce designs.