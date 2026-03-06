-- Initial Data Seeding for Car Hire

USE car_hire;

-- Seed Vehicles
INSERT INTO vehicles (make, model, year, capacity, price_per_day, status, image_url) VALUES
('Toyota', 'Land Cruiser Prado', 2022, 7, 1500.00, 'available', 'public/images/cars/prado.jpg'),
('Toyota', 'Hilux GD-6', 2021, 5, 1200.00, 'available', 'public/images/cars/hilux.png'),
('Ford', 'Ranger Wildtrak', 2023, 5, 1300.00, 'available', 'public/images/cars/ranger.png'),
('Volkswagen', 'Golf 7 TSI', 2020, 5, 800.00, 'available', 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?q=80&w=1000&auto=format&fit=crop'),
('Land Rover', 'Defender 110', 2023, 7, 2500.00, 'available', 'https://images.unsplash.com/photo-1616422285623-13ff0167c958?q=80&w=1000&auto=format&fit=crop'),
('Mercedes-Benz', 'G63 AMG', 2022, 5, 5000.00, 'available', 'https://images.unsplash.com/photo-1520031441872-265e4ff70366?q=80&w=1000&auto=format&fit=crop'),
('BMW', 'X5 xDrive40i', 2021, 5, 2200.00, 'available', 'https://images.unsplash.com/photo-1555215695-3004980ad54e?q=80&w=1000&auto=format&fit=crop'),
('Toyota', 'Corolla Quest', 2020, 5, 600.00, 'available', 'https://images.unsplash.com/photo-1623860841270-1793699b6f84?q=80&w=1000&auto=format&fit=crop'),
('Nissan', 'Navara PRO-4X', 2022, 5, 1250.00, 'available', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=1000&auto=format&fit=crop'),
('Mitsubishi', 'Pajero Sport', 2021, 7, 1400.00, 'available', 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?q=80&w=1000&auto=format&fit=crop'),
('Toyota', 'Camry', 2022, 5, 1100.00, 'available', 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?q=80&w=1000&auto=format&fit=crop'),
('Audi', 'Q7 Quattro', 2022, 7, 2400.00, 'available', 'https://images.unsplash.com/photo-1541348263662-e0c8de4221fe?q=80&w=1000&auto=format&fit=crop'),
('Jeep', 'Grand Cherokee', 2021, 5, 1800.00, 'available', 'https://images.unsplash.com/photo-1539414417088-348620803cbe?q=80&w=1000&auto=format&fit=crop'),
('Toyota', 'Rav4', 2021, 5, 1000.00, 'available', 'https://images.unsplash.com/photo-1594535182308-8ffefbb661e1?q=80&w=1000&auto=format&fit=crop'),
('Hyundai', 'Santa Fe', 2022, 7, 1300.00, 'available', 'https://images.unsplash.com/photo-1616422329260-23facca31293?q=80&w=1000&auto=format&fit=crop'),
('Suzuki', 'Jimny', 2023, 4, 750.00, 'available', 'https://images.unsplash.com/photo-1614741369527-0cf11c5218d6?q=80&w=1000&auto=format&fit=crop'),
('Toyota', 'Alphard', 2020, 8, 2000.00, 'available', 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?q=80&w=1000&auto=format&fit=crop'),
('Ford', 'Everest', 2022, 7, 1600.00, 'available', 'https://images.unsplash.com/photo-1611016186353-9af58c69a533?q=80&w=1000&auto=format&fit=crop');



-- Seed Branches
INSERT INTO branches (name, address, city, phone) VALUES 
('Lusaka Main', 'Great East Road', 'Lusaka', '0211123456'),
('Ndola Branch', 'President Avenue', 'Ndola', '0212123456'),
('Livingstone Airport', 'Harry Mwaanga Nkumbula Airport', 'Livingstone', '0213123456');

-- Seed Add-ons
INSERT INTO add_ons (name, description, price_per_day) VALUES 
('Comprehensive Insurance', 'Full coverage for any damages or theft.', 150.00),
('GPS Navigation', 'High-accuracy GPS for easy navigation.', 50.00),
('Extra Driver', 'Add another driver to the rental agreement.', 100.00),
('Child Seat', 'Comfortable and safe seat for children.', 40.00);

-- Default Users (Password for all: password123)
-- Admin
INSERT INTO users (name, email, password, role) VALUES
('System Admin', 'admin@CarHire.zm', '$2y$10$86q7yB.QkGk7S9Vl5rKz9eW3vY9bX8mBv7kY6n5m4l3k2j1i0h9gG', 'admin');

-- Agent
INSERT INTO users (name, email, password, role) VALUES
('John Agent', 'agent@CarHire.zm', '$2y$10$86q7yB.QkGk7S9Vl5rKz9eW3vY9bX8mBv7kY6n5m4l3k2j1i0h9gG', 'agent');

-- Customer
INSERT INTO users (name, email, password, role) VALUES
('Jane Customer', 'customer@CarHire.zm', '$2y$10$8v/f6N.2pW1R3o6yH7mBeuIuK7kXyN.L9mBeuIuK7kXyN.L9mBeu.', 'customer');
