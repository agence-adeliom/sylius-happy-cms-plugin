<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250702192055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE sylius_happy_cms__redirect_route (id INT UNSIGNED AUTO_INCREMENT NOT NULL, host VARCHAR(255) NOT NULL, schemes JSON NOT NULL, methods JSON NOT NULL, defaults JSON NOT NULL, requirements JSON NOT NULL, options JSON NOT NULL, `condition` VARCHAR(255) NOT NULL, variablePattern VARCHAR(255) DEFAULT NULL, staticPrefix VARCHAR(255) DEFAULT NULL, uri LONGTEXT DEFAULT NULL, routeName VARCHAR(255) DEFAULT NULL, permanent TINYINT(1) DEFAULT 0 NOT NULL, routeTargetId INT UNSIGNED DEFAULT NULL, UNIQUE INDEX UNIQ_4BF1D16B91F30BA8 (routeName), INDEX IDX_4BF1D16B4C0848C6 (routeTargetId), INDEX IDX_4BF1D16BA5B5867E (staticPrefix), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE sylius_happy_cms__route (id INT UNSIGNED AUTO_INCREMENT NOT NULL, host VARCHAR(255) NOT NULL, schemes JSON NOT NULL, methods JSON NOT NULL, defaults JSON NOT NULL, requirements JSON NOT NULL, options JSON NOT NULL, `condition` VARCHAR(255) NOT NULL, variablePattern VARCHAR(255) DEFAULT NULL, staticPrefix VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, position INT NOT NULL, UNIQUE INDEX UNIQ_C7D98F815E237E06 (name), INDEX IDX_C7D98F81A5B5867E (staticPrefix), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sylius_happy_cms__redirect_route ADD CONSTRAINT FK_4BF1D16B4C0848C6 FOREIGN KEY (routeTargetId) REFERENCES sylius_happy_cms__route (id) ON DELETE SET NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE sylius_happy_cms__redirect_route DROP FOREIGN KEY FK_4BF1D16B4C0848C6
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sylius_happy_cms__redirect_route
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sylius_happy_cms__route
        SQL);
    }
}
