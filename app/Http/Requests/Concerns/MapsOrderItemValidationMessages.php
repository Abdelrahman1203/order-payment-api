<?php

namespace App\Http\Requests\Concerns;

trait MapsOrderItemValidationMessages
{
    /**
     * @return array<string, string>
     */
    protected function orderItemValidationMessages(): array
    {
        return [
            'items.required' => 'Please add at least one product to the order.',
            'items.array' => 'Order items must be sent as a list of products.',
            'items.min' => 'Please add at least one product to the order.',
            'items.max' => 'An order cannot contain more than 50 products.',

            'items.*.product_name.required' => 'Each product must have a name.',
            'items.*.product_name.required_with' => 'Each product must have a name when updating order items.',
            'items.*.product_name.string' => 'Product name must be text.',
            'items.*.product_name.max' => 'Product name cannot be longer than 255 characters.',

            'items.*.quantity.required' => 'Please enter how many units you need for each product.',
            'items.*.quantity.required_with' => 'Please enter how many units you need for each product.',
            'items.*.quantity.integer' => 'Product quantity must be a whole number.',
            'items.*.quantity.min' => 'Product quantity must be at least 1.',
            'items.*.quantity.max' => 'Product quantity cannot exceed 9,999.',

            'items.*.price.required' => 'Please enter the unit price for each product.',
            'items.*.price.required_with' => 'Please enter the unit price for each product.',
            'items.*.price.numeric' => 'Product price must be a valid number.',
            'items.*.price.decimal' => 'Product price must have at most 2 decimal places.',
            'items.*.price.min' => 'Product price cannot be negative.',
            'items.*.price.max' => 'Product price cannot exceed 999,999.99.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function orderCustomerValidationMessages(): array
    {
        return [
            'customer_name.required' => 'Please enter the customer\'s full name.',
            'customer_name.string' => 'Customer name must be text.',
            'customer_name.max' => 'Customer name cannot be longer than 255 characters.',
            'customer_name.filled' => 'Please enter the customer\'s full name.',

            'customer_email.required' => 'Please enter the customer\'s email address.',
            'customer_email.email' => 'Please enter a valid customer email address.',
            'customer_email.max' => 'Customer email cannot be longer than 255 characters.',
            'customer_email.filled' => 'Please enter the customer\'s email address.',

            'status.Illuminate\Validation\Rules\Enum' => 'Order status must be one of: pending, confirmed, or cancelled.',
            'status.in' => 'New orders can only be created with pending status.',
        ];
    }
}
