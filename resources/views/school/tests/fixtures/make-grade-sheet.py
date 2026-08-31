import sys

cells = [
    (50, 740, "No"), (95, 740, "Name"), (240, 740, "First Term"), (400, 740, "Total"),
    (205, 715, "Quiz"), (265, 715, "Homework"), (350, 715, "Exam"),
    (52, 685, "1"), (95, 685, "Ahmad Ali"), (212, 685, "8"), (287, 685, "9"), (358, 685, "18"), (405, 685, "35"),
    (52, 655, "2"), (95, 655, "Sami Nour"), (212, 655, "7"), (284, 655, "10"), (358, 655, "19"), (405, 655, "36"),
    (52, 625, "3"), (95, 625, "Lina Odeh"), (212, 625, "9"), (284, 625, "10"), (358, 625, "20"), (405, 625, "39"),
]

parts = ["BT /F1 11 Tf"]
for x, y, text in cells:
    parts.append("1 0 0 1 %d %d Tm (%s) Tj" % (x, y, text))
parts.append("ET")
stream = "\n".join(parts).encode("latin-1")

objects = [
    b"<</Type/Catalog/Pages 2 0 R>>",
    b"<</Type/Pages/Kids[3 0 R]/Count 1>>",
    b"<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Resources<</Font<</F1 5 0 R>>>>/Contents 4 0 R>>",
    b"<</Length " + str(len(stream)).encode() + b">>\nstream\n" + stream + b"\nendstream",
    b"<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>",
]

out = bytearray(b"%PDF-1.4\n")
offsets = []
for index, body in enumerate(objects, start=1):
    offsets.append(len(out))
    out += str(index).encode() + b" 0 obj\n" + body + b"\nendobj\n"

xref = len(out)
out += b"xref\n0 " + str(len(objects) + 1).encode() + b"\n0000000000 65535 f \n"
for offset in offsets:
    out += ("%010d 00000 n \n" % offset).encode()
out += b"trailer\n<</Size " + str(len(objects) + 1).encode() + b"/Root 1 0 R>>\nstartxref\n" + str(xref).encode() + b"\n%%EOF\n"

open(sys.argv[1], "wb").write(bytes(out))
print("wrote", sys.argv[1], len(out), "bytes")
