# how to build a payment library use stratey design mode in PHP?

To build a payment library using the Strategy design pattern in PHP, you can follow these steps. The Strategy pattern allows you to define a family of algorithms, encapsulate each one, and make them interchangeable. This pattern is particularly useful for payment processing, where different payment methods can be implemented as separate strategies.

## Step-by-Step Implementation
### Define the Strategy Interface

Create an interface that all payment strategies will implement. This interface should define the method(s) that each payment strategy must have.

```
<?php

interface PaymentStrategy
{
    public function pay($amount);
}
```
### Implement Concrete Strategies

Implement the concrete strategies for each payment method (e.g., PayPal, Credit Card, etc.).

```
<?php

class PayPalStrategy implements PaymentStrategy
{
    private $email;
    private $password;

    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public function pay($amount)
    {
        // Implement PayPal payment process here
        echo "Paying $amount using PayPal.";
    }
}

class CreditCardStrategy implements PaymentStrategy
{
    private $cardNumber;
    private $cvv;
    private $expiryDate;

    public function __construct($cardNumber, $cvv, $expiryDate)
    {
        $this->cardNumber = $cardNumber;
        $this->cvv = $cvv;
        $this->expiryDate = $expiryDate;
    }

    public function pay($amount)
    {
        // Implement credit card payment process here
        echo "Paying $amount using Credit Card.";
    }
}
```
### Create the Context Class

Create a context class that will use a PaymentStrategy to process payments. This class should allow setting different strategies dynamically.

```
<?php

class PaymentContext
{
    private $strategy;

    public function setPaymentStrategy(PaymentStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function pay($amount)
    {
        $this->strategy->pay($amount);
    }
}
```
### Using the Payment Library

Now, you can use the PaymentContext class to process payments with different strategies.

```
<?php

// Example usage:

$context = new PaymentContext();

// Pay using PayPal
$paypal = new PayPalStrategy("user@example.com", "password123");
$context->setPaymentStrategy($paypal);
$context->pay(100); // Output: Paying 100 using PayPal.

echo "\n";

// Pay using Credit Card
$creditCard = new CreditCardStrategy("1234567890123456", "123", "12/23");
$context->setPaymentStrategy($creditCard);
$context->pay(200); // Output: Paying 200 using Credit Card.
```
### Summary
Summary
Define the Strategy Interface: This ensures that all payment strategies will have a common method to implement.
Implement Concrete Strategies: Each payment method (strategy) implements the strategy interface.
Create the Context Class: This class uses the strategy to process payments and allows changing strategies dynamically.
Use the Library: Instantiate the context, set the desired payment strategy, and process the payment.
This approach makes it easy to add new payment methods without changing the existing code, adhering to the open/closed principle.


# And add a factory class to generate strategy by pay method

Sure! Adding a factory class to generate the strategy based on the payment method will further streamline the process of selecting the appropriate payment strategy. Here's how you can implement it:

## Step-by-Step Implementation with Factory Class
### Define the Strategy Interface

(Already defined in the previous step.)

```
<?php

interface PaymentStrategy
{
    public function pay($amount);
}
```
### Implement Concrete Strategies

(Already implemented in the previous step.)

```
<?php

class PayPalStrategy implements PaymentStrategy
{
    private $email;
    private $password;

    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public function pay($amount)
    {
        // Implement PayPal payment process here
        echo "Paying $amount using PayPal.";
    }
}

class CreditCardStrategy implements PaymentStrategy
{
    private $cardNumber;
    private $cvv;
    private $expiryDate;

    public function __construct($cardNumber, $cvv, $expiryDate)
    {
        $this->cardNumber = $cardNumber;
        $this->cvv = $cvv;
        $this->expiryDate = $expiryDate;
    }

    public function pay($amount)
    {
        // Implement credit card payment process here
        echo "Paying $amount using Credit Card.";
    }
}
```
### Create the Context Class

(Already created in the previous step.)

```
<?php

class PaymentContext
{
    private $strategy;

    public function setPaymentStrategy(PaymentStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function pay($amount)
    {
        $this->strategy->pay($amount);
    }
}
```
### Implement the Factory Class

Create a factory class to generate the appropriate payment strategy based on the payment method.

```
<?php

class PaymentStrategyFactory
{
    public static function createStrategy($method, $params)
    {
        switch ($method) {
            case 'paypal':
                return new PayPalStrategy($params['email'], $params['password']);
            case 'credit_card':
                return new CreditCardStrategy($params['cardNumber'], $params['cvv'], $params['expiryDate']);
            default:
                throw new Exception("Unknown payment method: $method");
        }
    }
}
```
### Using the Payment Library with Factory

Now, you can use the factory to create the appropriate payment strategy and use it with the context.

```
<?php

// Example usage:

$context = new PaymentContext();

// Parameters for PayPal
$paypalParams = [
    'email' => 'user@example.com',
    'password' => 'password123'
];
$paypalStrategy = PaymentStrategyFactory::createStrategy('paypal', $paypalParams);
$context->setPaymentStrategy($paypalStrategy);
$context->pay(100); // Output: Paying 100 using PayPal.

echo "\n";

// Parameters for Credit Card
$creditCardParams = [
    'cardNumber' => '1234567890123456',
    'cvv' => '123',
    'expiryDate' => '12/23'
];
$creditCardStrategy = PaymentStrategyFactory::createStrategy('credit_card', $creditCardParams);
$context->setPaymentStrategy($creditCardStrategy);
$context->pay(200); // Output: Paying 200 using Credit Card.
```
### Summary
Define the Strategy Interface: Ensures all payment strategies have a common method to implement.
Implement Concrete Strategies: Each payment method (strategy) implements the strategy interface.
Create the Context Class: Uses the strategy to process payments and allows changing strategies dynamically.
Implement the Factory Class: Generates the appropriate payment strategy based on the payment method.
Use the Library with Factory: The factory simplifies the creation of the payment strategy and integrates seamlessly with the context.
This approach further abstracts the creation of payment strategies, making the system even more flexible and easier to maintain.