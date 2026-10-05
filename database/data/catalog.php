<?php

// Starting catalog, loaded once by the catalog migration. Edit plans in the admin afterwards.
return array (
  'groups' => 
  array (
    0 => 
    array (
      'key' => 'cpanel',
      'checkout_provider' => 'whmcs',
      'ui_tone' => 'rose',
      'sort_order' => 0,
      'content' => 
      array (
        'en' => 
        array (
          'name' => 'cPanel',
          'title' => 'Cloud Hosting Powered by cPanel',
          'summary' => 'Easy-to-use hosting for websites, blogs, portfolios, and business landing pages.',
          'highlights' => 
          array (
            0 => 'cPanel control panel',
            1 => 'SSL security',
            2 => 'Business email support',
            3 => 'WordPress toolkit',
            4 => '24/7 expert support',
            5 => 'Great for small and medium businesses',
          ),
        ),
      ),
    ),
    1 => 
    array (
      'key' => 'vps',
      'checkout_provider' => 'internal',
      'ui_tone' => 'slate',
      'sort_order' => 1,
      'content' => 
      array (
        'en' => 
        array (
          'name' => 'AMD EPYC',
          'title' => 'VPS Root Server',
          'summary' => 'Dedicated server resources for businesses that need more power, control, and scalability.',
          'highlights' => 
          array (
            0 => 'Root access',
            1 => 'Better performance',
            2 => 'Scalable server resources',
            3 => '24/7 expert support',
            4 => 'Suitable for apps and high-traffic sites',
            5 => 'For developers and advanced users',
          ),
        ),
      ),
    ),
  ),
  'plans' => 
  array (
    0 => 
    array (
      'group' => 'cpanel',
      'key' => 'starter',
      'sort_order' => 0,
      'is_featured' => false,
      'price_ngn' => 39000.0,
      'specs' => 
      array (
        0 => 
        array (
          'key' => 'storage',
          'label' => 
          array (
            'en' => 'Storage',
          ),
          'value' => 
          array (
            'en' => '15 GB SSD',
          ),
        ),
        1 => 
        array (
          'key' => 'bandwidth',
          'label' => 
          array (
            'en' => 'Bandwidth',
          ),
          'value' => 
          array (
            'en' => 'Unmetered',
          ),
        ),
        2 => 
        array (
          'key' => 'websites',
          'label' => 
          array (
            'en' => 'Websites',
          ),
          'value' => 
          array (
            'en' => '1 website',
          ),
        ),
      ),
      'content' => 
      array (
        'en' => 
        array (
          'label' => 'Starter',
          'description' => 'Great for small websites and landing pages.',
          'best_for' => 'Personal sites, portfolios, and early-stage business landing pages.',
          'highlights' => 
          array (
            0 => 'cPanel control panel',
            1 => 'Free SSL certificate',
            2 => 'Daily backups',
          ),
          'includes' => 
          array (
            0 => '1 hosted website',
            1 => 'Business email ready',
            2 => 'WordPress one-click install',
            3 => '24/7 support',
          ),
          'badge' => '',
        ),
      ),
    ),
    1 => 
    array (
      'group' => 'cpanel',
      'key' => 'business',
      'sort_order' => 1,
      'is_featured' => true,
      'price_ngn' => 95000.0,
      'specs' => 
      array (
        0 => 
        array (
          'key' => 'storage',
          'label' => 
          array (
            'en' => 'Storage',
          ),
          'value' => 
          array (
            'en' => '40 GB SSD',
          ),
        ),
        1 => 
        array (
          'key' => 'bandwidth',
          'label' => 
          array (
            'en' => 'Bandwidth',
          ),
          'value' => 
          array (
            'en' => 'Unmetered',
          ),
        ),
        2 => 
        array (
          'key' => 'websites',
          'label' => 
          array (
            'en' => 'Websites',
          ),
          'value' => 
          array (
            'en' => 'Up to 5 websites',
          ),
        ),
      ),
      'content' => 
      array (
        'en' => 
        array (
          'label' => 'Business',
          'description' => 'Balanced resources for business websites and blogs.',
          'best_for' => 'Growing businesses running blogs, company sites, and client portfolios.',
          'highlights' => 
          array (
            0 => 'More storage & bandwidth',
            1 => 'Multiple site hosting',
            2 => 'Priority support queue',
          ),
          'includes' => 
          array (
            0 => 'Up to 5 hosted websites',
            1 => 'Enhanced backup retention',
            2 => 'Staging-friendly resources',
            3 => '24/7 expert support',
          ),
          'badge' => '',
        ),
      ),
    ),
    2 => 
    array (
      'group' => 'cpanel',
      'key' => 'pro',
      'sort_order' => 2,
      'is_featured' => false,
      'price_ngn' => 190000.0,
      'specs' => 
      array (
        0 => 
        array (
          'key' => 'storage',
          'label' => 
          array (
            'en' => 'Storage',
          ),
          'value' => 
          array (
            'en' => '80 GB SSD',
          ),
        ),
        1 => 
        array (
          'key' => 'bandwidth',
          'label' => 
          array (
            'en' => 'Bandwidth',
          ),
          'value' => 
          array (
            'en' => 'Unmetered',
          ),
        ),
        2 => 
        array (
          'key' => 'websites',
          'label' => 
          array (
            'en' => 'Websites',
          ),
          'value' => 
          array (
            'en' => 'Unlimited websites',
          ),
        ),
      ),
      'content' => 
      array (
        'en' => 
        array (
          'label' => 'Pro',
          'description' => 'Higher performance for heavier traffic and ecommerce.',
          'best_for' => 'Online stores, high-traffic sites, and teams running multiple properties.',
          'highlights' => 
          array (
            0 => 'Highest shared performance',
            1 => 'Ecommerce-ready resources',
            2 => 'Advanced backup options',
          ),
          'includes' => 
          array (
            0 => 'Unlimited hosted websites',
            1 => 'Optimized for WooCommerce & WordPress',
            2 => 'Higher concurrent visitor capacity',
            3 => 'Priority 24/7 support',
          ),
          'badge' => '',
        ),
      ),
    ),
    3 => 
    array (
      'group' => 'vps',
      'key' => 'vps-2',
      'sort_order' => 0,
      'is_featured' => false,
      'price_ngn' => 140000.0,
      'specs' => 
      array (
        0 => 
        array (
          'key' => 'storage',
          'label' => 
          array (
            'en' => 'Storage',
          ),
          'value' => 
          array (
            'en' => '80 GB NVMe',
          ),
        ),
        1 => 
        array (
          'key' => 'cpu',
          'label' => 
          array (
            'en' => 'CPU',
          ),
          'value' => 
          array (
            'en' => '2 vCPU',
          ),
        ),
        2 => 
        array (
          'key' => 'ram',
          'label' => 
          array (
            'en' => 'RAM',
          ),
          'value' => 
          array (
            'en' => '4 GB RAM',
          ),
        ),
      ),
      'content' => 
      array (
        'en' => 
        array (
          'label' => 'VPS 2',
          'description' => 'Entry VPS for lightweight production workloads.',
          'best_for' => 'Side projects, staging environments, and small production apps.',
          'highlights' => 
          array (
            0 => 'Full root access',
            1 => 'AMD EPYC processors',
            2 => 'Dedicated resources',
          ),
          'includes' => 
          array (
            0 => 'Root / SSH access',
            1 => 'NVMe storage',
            2 => 'Custom stack installation',
            3 => '24/7 expert support',
          ),
          'badge' => '',
        ),
      ),
    ),
    4 => 
    array (
      'group' => 'vps',
      'key' => 'vps-4',
      'sort_order' => 1,
      'is_featured' => true,
      'price_ngn' => 280000.0,
      'specs' => 
      array (
        0 => 
        array (
          'key' => 'storage',
          'label' => 
          array (
            'en' => 'Storage',
          ),
          'value' => 
          array (
            'en' => '160 GB NVMe',
          ),
        ),
        1 => 
        array (
          'key' => 'cpu',
          'label' => 
          array (
            'en' => 'CPU',
          ),
          'value' => 
          array (
            'en' => '4 vCPU',
          ),
        ),
        2 => 
        array (
          'key' => 'ram',
          'label' => 
          array (
            'en' => 'RAM',
          ),
          'value' => 
          array (
            'en' => '8 GB RAM',
          ),
        ),
      ),
      'content' => 
      array (
        'en' => 
        array (
          'label' => 'VPS 4',
          'description' => 'Balanced VPS for medium traffic services.',
          'best_for' => 'Business APIs, Docker workloads, and medium-traffic web services.',
          'highlights' => 
          array (
            0 => 'Balanced CPU & memory',
            1 => 'Ideal for APIs & apps',
            2 => 'Scalable architecture',
          ),
          'includes' => 
          array (
            0 => 'Root / SSH access',
            1 => 'Higher I/O performance',
            2 => 'Database-friendly RAM',
            3 => 'Priority support queue',
          ),
          'badge' => '',
        ),
      ),
    ),
    5 => 
    array (
      'group' => 'vps',
      'key' => 'vps-8',
      'sort_order' => 2,
      'is_featured' => false,
      'price_ngn' => 560000.0,
      'specs' => 
      array (
        0 => 
        array (
          'key' => 'storage',
          'label' => 
          array (
            'en' => 'Storage',
          ),
          'value' => 
          array (
            'en' => '320 GB NVMe',
          ),
        ),
        1 => 
        array (
          'key' => 'cpu',
          'label' => 
          array (
            'en' => 'CPU',
          ),
          'value' => 
          array (
            'en' => '8 vCPU',
          ),
        ),
        2 => 
        array (
          'key' => 'ram',
          'label' => 
          array (
            'en' => 'RAM',
          ),
          'value' => 
          array (
            'en' => '16 GB RAM',
          ),
        ),
      ),
      'content' => 
      array (
        'en' => 
        array (
          'label' => 'VPS 8',
          'description' => 'High-performance VPS for heavy applications.',
          'best_for' => 'High-traffic platforms, multi-service stacks, and resource-intensive apps.',
          'highlights' => 
          array (
            0 => 'Maximum VPS performance',
            1 => 'Heavy workload ready',
            2 => 'Enterprise-grade headroom',
          ),
          'includes' => 
          array (
            0 => 'Root / SSH access',
            1 => '320 GB NVMe storage',
            2 => 'High concurrent processing',
            3 => 'Priority 24/7 support',
          ),
          'badge' => '',
        ),
      ),
    ),
  ),
);
