# Custom WC Endpoints Plugin

## Introduction
This documentation provides an overview of the Custom WC Endpoints Plugin developed for managing various functionalities related to products, categories, search, checkout, user profiles, and authentication. This plugin extends the default WordPress functionality by providing custom endpoints and hooks to control access and behavior based on user roles.

## Before Installation
Before installing the custom plugin, make sure to install the required plugins and configure them accordingly:

1. [Woocommerce](https://wordpress.org/plugins/woocommerce/): 
   - Woocommerce is required to utilize custom endpoints for products and categories. Make sure to install and activate the Woocommerce plugin before proceeding.

2. [Advanced Access Manager (AAM):](https://wordpress.org/plugins/advanced-access-manager/)
   - AAM plugin is essential for authentication operations and enables the use of JWT (JSON Web Token) during each request. It provides fine-grained access control.

3. [CoCart:](https://wordpress.org/plugins/cart-rest-api-for-woocommerce/)
   - CoCart plugin is required to utilize the user cart functionality through the REST API. It allows you to manage and manipulate the cart items via API endpoints.

Please note that certain modifications are required for these plugins, which will be discussed in detail at the end of the documentation. Make sure to refer to the modifications section to ensure proper integration and compatibility between the custom plugin and the aforementioned plugins.

### Create Custom Access Capability

Before utilizing this plugin, it is necessary to create a new capability called `fetch-products`. This capability will define the permission level required to access the custom endpoints. It can be created through the AAM plugin. Make sure it exactly matches the name of the `fetch-products`. Then assign this capability to the roles or users who should have permission to use these endpoints.


## Custom Endpoints

### 1. Get Products
Endpoint: `/lamah/v1/products`

This endpoint retrieves a list of products based on the user's role, ensuring appropriate access based on user privileges.

Parameters:

- `page`: Specifies the page number of the product list. Use this parameter to navigate through paginated results. Example: `page=5` (default: 1)
- `per_page`: Specifies the number of products per page. Use this parameter to control the number of products to display on each page. Example: `per_page=10` (default: 12)
- `category`: Filters the products by category. Use the category ID to retrieve products belonging to a specific category. Example: `category=64`
- `search`: Searches for products based on a keyword or phrase. Use this parameter to retrieve products that match the search query. Example: `search=WB3`

### 2. Get Categories
Endpoint: `/lamah/v1/categories`

This endpoint allows retrieving a list of categories based on the user's role. The response includes the category ID, category name, and the count of products within each category. Empty categories are excluded from the response, ensuring that only categories with associated products are returned.

## Custom Checkout
Endpoint: `/lamah/v1/checkout`

This endpoint allows users to create an order directly from their cart. This endpoint retrieves the necessary data from the user's cart, creates an order based on that data, and clears the cart afterward.
To utilize this custom checkout feature, it is required to have the Cocart plugin installed.

## Fetch User Profile Data
Endpoint: `/lamah/v1/user/profile`

The response includes the user's ID, username, email address, first name, last name, role, and city. By accessing this endpoint, developers can retrieve user-specific information and utilize it within the WordPress environment.

## Fetch User Billing Address
Endpoint: `/lamah/v1/user/billing-address`

The response includes fields representing the user's first name, last name, company, address line 1, address line 2, city, postcode, country, state, email address, and phone number. This endpoint enables developers to access and utilize the user's billing address information for various purposes within the WordPress environment.

## Custom Filter Hook

### 1. AAM Auth Response Filter
Hook: `aam_auth_response_filter`

This hook allows customizing the authentication response to include additional data relevant to user roles.

### 2. Custom Authenticate Filter
Hook: `custom_authenticate_filter`

This hook allows deny authentication for users with a suspended role, implementing specific actions or restrictions for such users.


### 3. Custom Hooks File
The custom hooks mentioned above are implemented in the `custom-hooks.php` file. This file contains the necessary code to extend and modify WordPress functionality using hooks and filters.



## Modifications in specific plugins:

### Advanced Access Manager (AAM):

1. File: `advanced-access-manager/application/Service/SecureLogin.php`

```php
//..
public function authenticate(WP_REST_Request $request)
{
    // ...
    // Custom code
    // Apply custom filter to modify $result and $status
    $filtered_result = apply_filters('custom_authenticate_filter', $result, $status, $request);
    if(isset($filtered_result['result']) && isset($filtered_result['status'])){
        $result = $filtered_result['result'];
        $status = $filtered_result['status'];
    }

    return new WP_REST_Response($result, $status);
}
//..
```

- Added a custom filter hook to prevent users from logging in with the 'suspend' role. The filter code is implemented in the custom-wc-endpoints plugin.

## Mistakes that may occur

When attempting authentication, you may encounter the need for a refreshable JWT token. To address this, make sure to include the `refreshableJWT` property in the request body, as shown below:

```json
{
  "username": "username",
  "password": "password",
  "issueJWT": true,
  "refreshableJWT": true
}
```

If you encounter an error when using the `aam/v2/authenticate` endpoint, with a status code of 400 and the following response:

```json
{
  "reason": "Current user is not allowed to issue refreshable JWT token"
}
```

To resolve this issue, follow the steps below:

1. Go to the AAM plugin settings.
2. Navigate to the "capabilities" section.
3. Create a new capability named `aam_issue_refreshable_jwt`.
4. Assign this capability to all relevant roles that need the ability to issue refreshable JWT tokens.