using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace PhpCloudBoxClientLibrary
{
	public class TrackingStream : ProxyStream
	{
		public TrackingStream(Stream ParentStream)
			: base(ParentStream)
		{
		}

		public event Action<long> OnPositionChanged;

		public override long Position
		{
			get
			{
				return base.Position;
			}
			set
			{
				base.Position = value;
				OnPositionChanged(base.Position);
			}
		}

		public override int Read(byte[] buffer, int offset, int count)
		{
			var Result = base.Read(buffer, offset, count);
			OnPositionChanged(this.Position);
			return Result;
		}

		public override void Write(byte[] buffer, int offset, int count)
		{
			base.Write(buffer, offset, count);
			OnPositionChanged(this.Position);
		}

		public override long Seek(long offset, SeekOrigin origin)
		{
			var Result = base.Seek(offset, origin);
			OnPositionChanged(this.Position);
			return Result;
		}

		async public override Task<int> ReadAsync(byte[] buffer, int offset, int count, System.Threading.CancellationToken cancellationToken)
		{
			var Result = await base.ReadAsync(buffer, offset, count, cancellationToken);
			OnPositionChanged(this.Position);
			return Result;
		}

		async public override Task WriteAsync(byte[] buffer, int offset, int count, System.Threading.CancellationToken cancellationToken)
		{
			await base.WriteAsync(buffer, offset, count, cancellationToken);
			OnPositionChanged(this.Position);
		}
	}
}
