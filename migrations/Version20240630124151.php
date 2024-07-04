<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240630124151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
		$this->addSql(<<<SQL
			CREATE TABLE `user` (
			    id INT AUTO_INCREMENT PRIMARY KEY, 
			    email VARCHAR(180) NOT NULL, 
			    roles JSON NOT NULL, 
			    `password` VARCHAR(255) NOT NULL, 
			    CONSTRAINT uq_user_email UNIQUE (email)
		    ) DEFAULT CHARACTER SET utf8mb3 
			  COLLATE `utf8mb3_unicode_ci`
			  ENGINE = InnoDB;
SQL
		);

        $this->addSql(<<<SQL
			CREATE TABLE `event` (
			    id INT AUTO_INCREMENT PRIMARY KEY, 
			    `name` VARCHAR(255) NOT NULL, 
			    `date` DATETIME NOT NULL, 
			    location VARCHAR(255) NOT NULL,
			    `description` LONGTEXT NOT NULL,
			    CONSTRAINT uq_event_name UNIQUE (`name`)
		    ) DEFAULT CHARACTER SET utf8mb3 
			  COLLATE `utf8mb3_unicode_ci` 
			  ENGINE = InnoDB;
SQL
		);

        $this->addSql(<<<SQL
			CREATE TABLE `order` (
			    id INT AUTO_INCREMENT PRIMARY KEY, 
			    user_id INT NOT NULL, 
			    `state` VARCHAR(25) NOT NULL, 
			    amount INT NOT NULL, 
			    payment_method VARCHAR(55) DEFAULT NULL, 
			    payment_attributes JSON DEFAULT NULL,
			    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
		    ) DEFAULT CHARACTER SET utf8mb3 
			  COLLATE `utf8mb3_unicode_ci` 
			  ENGINE = InnoDB;
SQL
		);

		$this->addSql(<<<SQL
			CREATE TABLE ticket (
			    id INT AUTO_INCREMENT PRIMARY KEY, 
			    event_id INT NOT NULL, 
			    price INT NOT NULL, 
			    quantity INT NOT NULL,
			    `description` LONGTEXT NOT NULL,
			    CONSTRAINT fk_ticket_event FOREIGN KEY (event_id) REFERENCES `event` (id) ON DELETE CASCADE
			) DEFAULT CHARACTER SET utf8mb3 
			  COLLATE `utf8mb3_unicode_ci` 
			  ENGINE = InnoDB;
SQL
		);

        $this->addSql(<<<SQL
			CREATE TABLE order_ticket (
			    id INT AUTO_INCREMENT PRIMARY KEY, 
			    order_id INT NOT NULL, 
			    ticket_id INT NOT NULL, 
			    quantity INT NOT NULL,
			    created_at DATETIME NOT NULL,
			    CONSTRAINT uq_order_ticket UNIQUE (order_id, ticket_id),
			    CONSTRAINT fk_order_ticket_order FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE,
			    CONSTRAINT fk_order_ticket_ticket FOREIGN KEY (ticket_id) REFERENCES ticket (id)
		    ) DEFAULT CHARACTER SET utf8mb3 
			  COLLATE `utf8mb3_unicode_ci` 
			  ENGINE = InnoDB;
SQL
		);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS order_ticket, ticket, `order`, event, `user`');
    }
}
