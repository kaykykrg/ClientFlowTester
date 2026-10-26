# Suíte de Testes Automatizados Robot Framework — ClientFlow

Documentação completa e comprobatória disponível em:
👉 [DOCUMENTACAO_TESTES_EVIDENCIAS.md](../../DOCUMENTACAO_TESTES_EVIDENCIAS.md)

## Como executar os testes

```powershell
# Execução de todos os 30 testes (5 por integrante x 6 integrantes):
robot --outputdir tests/robot/results tests/robot/

# Execução por integrante:
robot --include kayky tests/robot/
robot --include vantuil tests/robot/
robot --include arthur tests/robot/
robot --include matheus_pires tests/robot/
robot --include matheus_fonteles tests/robot/
robot --include yuri tests/robot/

# Execução por bloco:
robot --include bloco_a tests/robot/
robot --include bloco_b tests/robot/
robot --include bloco_c tests/robot/
robot --include bloco_d tests/robot/
robot --include bloco_e tests/robot/
robot --include bloco_f tests/robot/

# Executar script de extração e validação de evidências:
php tests/robot/evidence_runner.php
```

Os relatórios HTML gerados ficam em `tests/robot/results/report.html` e `tests/robot/results/log.html`.
