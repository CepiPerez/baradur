<?php

abstract class FakerCommerce extends FakerBase
{
    public static function productName()
    {
        return implode(' ', array(self::productAdjective(), self::productMaterial(), self::product()));
    }

    public static function productAdjective()
    {
        return self::pickOne(array(
            'Small', 'Ergonomic', 'Electronic', 'Rustic', 'Intelligent', 'Gorgeous', 'Incredible', 'Elegant', 
            'Fantastic', 'Practical', 'Modern', 'Recycled', 'Sleek', 'Bespoke', 'Awesome', 'Generic', 
            'Handcrafted', 'Handmade', 'Oriental', 'Licensed', 'Luxurious', 'Refined', 'Unbranded', 'Tasty'
        ));
    }

    public static function productMaterial()
    {
        return self::pickOne(array(
            'Steel', 'Bronze', 'Wooden', 'Concrete', 'Plastic', 'Cotton', 'Granite', 'Rubber', 'Metal', 'Soft', 'Fresh', 'Frozen'
        ));
    }

    public static function product()
    {
        return self::pickOne(array(
            'Chair', 'Car', 'Computer', 'Keyboard', 'Mouse', 'Bike', 'Ball', 'Gloves', 'Pants', 'Shirt', 'Table', 
            'Shoes', 'Hat', 'Towels', 'Soap', 'Tuna', 'Chicken', 'Fish', 'Cheese', 'Bacon', 'Pizza', 
            'Salad', 'Sausages', 'Chips'
        ));
    }

    public static function productDescription()
    {
        return self::pickOne(array(
            'Ergonomic executive chair upholstered in bonded black leather and PVC padded seat and back for all-day comfort and support',
            'The automobile layout consists of a front-engine design, with transaxle-type transmissions mounted at the rear of the engine and four wheel drive',
            'New ABC 13 9370, 13.3, 5th Gen CoreA5-8250U, 8GB RAM, 256GB SSD, power UHD Graphics, OS 10 Home, OS Office A & J 2016',
            'The slim & simple Maple Gaming Keyboard from Dev Byte comes with a sleek body and 7- Color RGB LED Back-lighting for smart functionality',
            'The Apollotech B340 is an affordable wireless mouse with reliable connectivity, 12 months battery life and modern design',
            'The Nagasaki Lander is the trademarked name of several series of Nagasaki sport bikes, that started with the 1984 ABC800J',
            'The Football Is Good For Training And Recreational Purposes',
            'Carbonite web goalkeeper gloves are ergonomically designed to give easy fit',
            "Boston's most advanced compression wear technology increases muscle oxygenation, stabilizes active muscles",
            'New range of formal shirts are designed keeping you in mind. With fits and styling that will make you stand apart',
            'The beautiful range of Apple Naturalé that has an exciting mix of natural ingredients. With the Goodness of 100% Natural Ingredients',
            'Andy shoes are designed to keeping in mind durability as well as trends, the most stylish range of shoes & sandals'
        ));
    }

    public static function department()
    {
        return self::pickOne(array(
            'Books', 'Movies', 'Music', 'Games', 'Electronics', 'Computers', 'Home', 'Garden', 'Tools', 'Grocery', 'Health', 'Beauty', 'Toys', 
            'Kids', 'Baby', 'Clothing', 'Shoes', 'Jewelery', 'Sports', 'Outdoors', 'Automotive', 'Industrial'
        ));
    }

    public static function color()
    {
        return self::pickOne(array(
            'red', 'green', 'blue', 'yellow', 'purple', 'mint green', 'teal', 'white', 'black', 'orange', 'pink', 'grey', 'maroon', 
            'violet', 'turquoise', 'tan', 'sky blue', 'salmon', 'plum', 'orchid', 'olive', 'magenta', 'lime', 'ivory', 'indigo', 'gold', 
            'fuchsia', 'cyan', 'azure', 'lavender', 'silver'
        ));
    }

    public static function price($min=1, $max=100, $decimals=2)
    {
        return number_format(rand($min, $max), $decimals);
    }



}
