<?php
// how-it-works.php
session_start();
require_once 'includes/Auth.php';
$user = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comment ça marche - PubWatch Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .steps-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .step-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 30px;
        }
        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            font-weight: bold;
        }
        .step-content {
            flex: 1;
        }
        .step-content h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .step-content p {
            color: #666;
        }
        .faq-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-top: 30px;
        }
        .faq-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .faq-question {
            font-weight: bold;
            color: #333;
            cursor: pointer;
        }
        .faq-answer {
            color: #666;
            margin-top: 10px;
            display: none;
        }
        .faq-item.active .faq-answer {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Navbar ici -->
    
    <div class="steps-container">
        <h1 style="text-align: center; color: white; margin-bottom: 30px;">📚 Comment ça marche ?</h1>
        
        <div class="step-card">
            <div class="step-number">1</div>
            <div class="step-content">
                <h3>Créez votre compte gratuitement</h3>
                <p>Inscrivez-vous en 30 secondes avec votre email et numéro de téléphone. C'est 100% gratuit !</p>
            </div>
        </div>
        
        <div class="step-card">
            <div class="step-number">2</div>
            <div class="step-content">
                <h3>Regardez des vidéos publicitaires</h3>
                <p>Choisissez parmi nos vidéos disponibles. Chaque vidéo dure entre 30 secondes et 2 minutes.</p>
            </div>
        </div>
        
        <div class="step-card">
            <div class="step-number">3</div>
            <div class="step-content">
                <h3>Gagnez 100 000 FCFA par vidéo</h3>
                <p>À la fin de chaque vidéo, votre solde est automatiquement crédité de 100 000 FCFA.</p>
            </div>
        </div>
        
        <div class="step-card">
            <div class="step-number">4</div>
            <div class="step-content">
                <h3>Retirez vos gains</h3>
                <p>Dès que vous atteignez 500 000 FCFA, demandez un retrait vers votre compte mobile money.</p>
            </div>
        </div>
        
        <div class="faq-section">
            <h2 style="margin-bottom: 20px;">❓ Questions fréquentes</h2>
            
            <div class="faq-item">
                <div class="faq-question">💰 Combien puis-je gagner par jour ?</div>
                <div class="faq-answer">Il n'y a pas de limite ! Plus vous regardez de vidéos, plus vous gagnez. Certains utilisateurs gagnent plus de 1 000 000 FCFA par jour.</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">📱 Comment sont effectués les paiements ?</div>
                <div class="faq-answer">Les paiements sont effectués via mobile money (Orange Money, Moov, Wave, MTN) sous 24-48h ouvrées.</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">🤝 Comment fonctionne le parrainage ?</div>
                <div class="faq-answer">Vous gagnez 10% des gains de vos filleuls à vie ! Plus vous parrainez, plus vous gagnez.</div>
            </div>
        </div>
    </div>
    
    <script>
    // FAQ accordéon
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', () => {
            const item = question.parentElement;
            item.classList.toggle('active');
        });
    });
    </script>
</body>
</html>