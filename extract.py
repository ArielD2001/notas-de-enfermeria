import sys
pdf_path = 'C:/Users/USER/Downloads/formularios/maternoinfantil.pdf'
out_path = 'C:/xampp/htdocs/codigos/notas_enfermeria/extracted_criteria.txt'
try:
    import PyPDF2
    with open(pdf_path, 'rb') as f, open(out_path, 'w', encoding='utf-8') as out:
        reader = PyPDF2.PdfReader(f)
        for i, page in enumerate(reader.pages):
            out.write(f"--- Page {i+1} ---\n")
            out.write(page.extract_text() + "\n\n")
    sys.exit(0)
except Exception as e:
    print("Error:", e)
