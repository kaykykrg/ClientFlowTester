*** Settings ***
Documentation     Suite principal do ClientFlow — Robot Framework
...               Agrupa os 30 casos de teste dos 6 blocos (A-F),
...               5 testes por integrante x 6 integrantes.
...
...               Para executar todos os testes:
...                 robot tests/robot/
...
...               Para executar por bloco (ex: bloco A):
...                 robot --include bloco_a tests/robot/
...
...               Para executar por integrante (ex: Kayky):
...                 robot --include kayky tests/robot/
...
...               Tags disponíveis por integrante:
...                 kayky, vantuil, arthur, matheus_pires, matheus_fonteles, yuri
...
...               Tags por tipo de teste:
...                 unitario, integracao, funcional, seguranca
...
...               Tags por bloco:
...                 bloco_a, bloco_b, bloco_c, bloco_d, bloco_e, bloco_f

Suite Setup       Log    Iniciando suite ClientFlow — 30 testes Robot Framework    console=True
Suite Teardown    Log    Suite concluída    console=True
