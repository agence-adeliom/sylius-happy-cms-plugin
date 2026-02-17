# Tests manuels - Vulnérabilité Path Traversal

## 📋 Prérequis

1. Application démarrée (`symfony server:start` ou `make up`)
2. Au moins un média uploadé dans la médiathèque
3. Outil de test: `curl`, Postman, ou navigateur

## 🧪 Tests à exécuter

### Test 1: Path Traversal classique (DOIT ÉCHOUER)

**Objectif:** Vérifier qu'on ne peut pas accéder aux fichiers système

```bash
# Tentative de lecture du fichier .env
curl -v http://localhost:8080/media/download/../../../.env

# Résultat attendu:
# HTTP/1.1 404 Not Found
# ou "Media not found"
```

**✅ Succès si:** Erreur 404 ou message "Media not found"
**❌ Échec si:** Le contenu du fichier .env s'affiche

---

### Test 2: Double Path Traversal (DOIT ÉCHOUER)

```bash
# Tentative avec multiples ../
curl -v "http://localhost:8080/media/download/../../../../../../../../etc/passwd"

# Résultat attendu:
# HTTP/1.1 404 Not Found
```

**✅ Succès si:** Erreur 404
**❌ Échec si:** Contenu de /etc/passwd visible

---

### Test 3: Path Traversal avec Backslash (DOIT ÉCHOUER)

```bash
# Tentative avec backslash (style Windows)
curl -v "http://localhost:8080/media/download/..\\..\\..\\config\\packages\\_security.yaml"

# Résultat attendu:
# HTTP/1.1 404 Not Found
```

**✅ Succès si:** Erreur 404
**❌ Échec si:** Configuration de sécurité visible

---

### Test 4: Null Byte Injection (DOIT ÉCHOUER)

```bash
# Tentative d'injection null byte
curl -v "http://localhost:8080/media/download/valid-file.jpg%00.php"

# Résultat attendu:
# HTTP/1.1 404 Not Found
```

**✅ Succès si:** Erreur 404
**❌ Échec si:** Fichier PHP exécuté ou affiché

---

### Test 5: Encodage URL (DOIT ÉCHOUER)

```bash
# Path traversal avec encodage URL
curl -v "http://localhost:8080/media/download/..%2F..%2F..%2F.env"

# Double encodage
curl -v "http://localhost:8080/media/download/%252e%252e%252f%252e%252e%252fconfig%252fservices.yaml"

# Résultat attendu:
# HTTP/1.1 404 Not Found
```

**✅ Succès si:** Erreur 404
**❌ Échec si:** Fichiers systèmes visibles

---

### Test 6: Accès légitime à un média (DOIT RÉUSSIR)

**Prérequis:** Uploader un fichier test via l'interface admin

```bash
# 1. Se connecter à l'admin
# 2. Aller dans la médiathèque
# 3. Uploader un fichier (ex: test-image.jpg)
# 4. Récupérer le path du fichier (ex: test-image.jpg ou dossier/test-image.jpg)

# 5. Tester le download
curl -v http://localhost:8080/media/download/test-image.jpg -o downloaded-file.jpg

# Résultat attendu:
# HTTP/1.1 200 OK
# Content-Type: image/jpeg
# Le fichier est téléchargé correctement
```

**✅ Succès si:**
- Status 200 OK
- Fichier téléchargé intact
- Bon Content-Type

**❌ Échec si:** Erreur 404 alors que le fichier existe

---

### Test 7: Accès à un média dans un sous-dossier (DOIT RÉUSSIR)

```bash
# 1. Créer un dossier "photos" dans la médiathèque
# 2. Uploader un fichier "vacation.jpg" dans ce dossier
# 3. Tester l'accès

curl -v http://localhost:8080/media/download/photos/vacation.jpg -o vacation.jpg

# Résultat attendu:
# HTTP/1.1 200 OK
```

**✅ Succès si:** Fichier téléchargé
**❌ Échec si:** Erreur 404

---

### Test 8: Médias non-existants (DOIT ÉCHOUER)

```bash
# Tentative d'accès à un fichier qui n'existe pas dans la DB
curl -v http://localhost:8080/media/download/non-existent-file.jpg

# Résultat attendu:
# HTTP/1.1 404 Not Found
# "Media not found"
```

**✅ Succès si:** Erreur 404 avec message générique
**❌ Échec si:** Exposition de détails techniques

---

## 📊 Résumé des résultats

| Test | Attendu | Résultat | Status |
|------|---------|----------|--------|
| Test 1: Path traversal .env | 404 | ⬜ | ⬜ |
| Test 2: Multiple ../ | 404 | ⬜ | ⬜ |
| Test 3: Backslash | 404 | ⬜ | ⬜ |
| Test 4: Null byte | 404 | ⬜ | ⬜ |
| Test 5: URL encoding | 404 | ⬜ | ⬜ |
| Test 6: Média légitime | 200 OK | ⬜ | ⬜ |
| Test 7: Média dans dossier | 200 OK | ⬜ | ⬜ |
| Test 8: Non-existant | 404 | ⬜ | ⬜ |

**Critère de succès:** ✅ Tous les tests doivent passer

---

## 🐛 Debugging

### Si un test échoue

1. **Vérifier les logs Symfony:**
```bash
tail -f var/log/dev.log
```

2. **Vérifier que les fichiers ont bien été modifiés:**
```bash
git diff src/Services/Media/MediaHelper.php
git diff src/Controller/Media/Module/Download.php
```

3. **Vider le cache:**
```bash
php bin/console cache:clear
```

4. **Vérifier la route:**
```bash
php bin/console debug:router | grep media
```

### Logs attendus pour tentatives d'attaque

Les tentatives de path traversal ne doivent PAS générer d'erreurs dans les logs, juste des 404 propres.

---

## 🔍 Tests automatisés (optionnel)

Pour créer des tests PHPUnit:

```php
<?php
// tests/Controller/Media/PathTraversalSecurityTest.php

namespace App\Tests\Controller\Media;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PathTraversalSecurityTest extends WebTestCase
{
    public function testPathTraversalIsBlocked(): void
    {
        $client = static::createClient();

        // Attempt path traversal
        $client->request('GET', '/media/download/../../../.env');

        // Should return 404, not the file content
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLegitimateFileAccessWorks(): void
    {
        $client = static::createClient();

        // Assuming a test media exists
        $client->request('GET', '/media/download/test-image.jpg');

        // Should return 200 if file exists in DB
        // or 404 if not (both are acceptable)
        $this->assertResponseIsSuccessful();
    }
}
```

Exécuter avec:
```bash
vendor/bin/phpunit tests/Controller/Media/PathTraversalSecurityTest.php
```

---

## ✅ Checklist finale

Avant de considérer la correction comme complète:

- [ ] Tous les tests de path traversal retournent 404
- [ ] Les médias légitimes sont toujours accessibles
- [ ] Aucun message d'erreur ne révèle des détails techniques
- [ ] Les logs ne montrent pas d'exceptions non gérées
- [ ] Les performances sont acceptables (< 100ms par requête)
- [ ] Le cache est vidé après déploiement
- [ ] La documentation SECURITY_PATH_TRAVERSAL_FIX.md est à jour

---

## 📞 Support

En cas de problème:
1. Vérifier que les modifications sont bien appliquées
2. Vider le cache Symfony
3. Consulter les logs
4. Créer une issue avec les détails des tests qui échouent