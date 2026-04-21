-- Run this in phpMyAdmin or MySQL terminal
-- Step 1: Create the database
CREATE DATABASE IF NOT EXISTS fitness_tracker;
USE fitness_tracker;

-- Step 2: Users table
CREATE TABLE users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email    VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Step 3: Workouts table
CREATE TABLE workouts (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    exercise_name VARCHAR(100) NOT NULL,
    sets          INT NOT NULL,
    reps          INT NOT NULL,
    weight        FLOAT NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Step 4: Meals table
CREATE TABLE meals (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    food_name  VARCHAR(100) NOT NULL,
    calories   INT NOT NULL,
    protein    FLOAT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
