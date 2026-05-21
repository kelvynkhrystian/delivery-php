-- Atualizar caminhos das imagens para incluir fallback PNG
UPDATE configuracoes 
SET logo = CASE 
    WHEN logo = 'assets/images/logo.svg' THEN 'assets/images/logo.png'
    ELSE logo 
END,
banner = CASE 
    WHEN banner = 'assets/images/banner.svg' THEN 'assets/images/banner.png'
    ELSE banner 
END,
favicon = CASE 
    WHEN favicon = 'assets/images/favicon.svg' THEN 'assets/images/favicon.png'
    ELSE favicon 
END
WHERE logo = 'assets/images/logo.svg' 
   OR banner = 'assets/images/banner.svg' 
   OR favicon = 'assets/images/favicon.svg';
